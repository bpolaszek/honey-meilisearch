<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Transport;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use Honey\ODM\Meilisearch\Criteria\CriteriaCompiler;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\DocumentResultset;
use Honey\ODM\Meilisearch\Result\SearchResultset;
use Meilisearch\Client;
use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Exceptions\ApiException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WeakMap;

use function array_column;
use function array_values;
use function Bentools\MeilisearchFilters\field;
use function Honey\ODM\Meilisearch\index_uid;
use function Honey\ODM\Meilisearch\iterable_chunk;

/**
 * @phpstan-type MeiliTransportOptions array{flushBatchSize?: int, flushTimeoutMs?: int, flushCheckIntervalMs?: int, wait?: bool}
 */
final readonly class MeiliTransport implements TransportInterface
{
    public const array DEFAULT_OPTIONS = [
        'flushBatchSize' => PHP_INT_MAX,
        'flushTimeoutMs' => 900_000,
        'flushCheckIntervalMs' => 50,
        'wait' => true,
    ];

    private OptionsResolver $optionsResolver;
    private CriteriaCompiler $criteriaCompiler;

    /**
     * @var MeiliTransportOptions
     */
    public private(set) array $options;

    /**
     * @param MeiliTransportOptions $options
     */
    public function __construct(
        public Client $meili,
        array $options = [],
        private string $indexPrefix = '',
    ) {
        $this->optionsResolver = new OptionsResolver();
        $this->optionsResolver->setDefaults(self::DEFAULT_OPTIONS);
        $this->optionsResolver->setAllowedTypes('flushBatchSize', ['int']);
        $this->optionsResolver->setAllowedTypes('flushTimeoutMs', ['int']);
        $this->optionsResolver->setAllowedTypes('flushCheckIntervalMs', ['int']);
        $this->optionsResolver->setAllowedTypes('wait', ['bool']);
        $this->options = $this->optionsResolver->resolve($options);
        $this->criteriaCompiler = new CriteriaCompiler($this->indexPrefix);
    }

    /**
     * Returns the (optionally prefixed) Meilisearch index UID for the given class metadata.
     *
     * @param AsDocument<object> $classMetadata
     */
    public function indexUid(AsDocument $classMetadata): string
    {
        return $this->indexPrefix . index_uid($classMetadata);
    }

    public function flushPendingOperations(UnitOfWork $unitOfWork, array $flushOptions = []): void
    {
        $tasks = [];
        $options = [] !== $flushOptions ? $this->optionsResolver->resolve([...$this->options, ...$flushOptions]) : $this->options;
        $flushBatchSize = $options['flushBatchSize'];
        $objectManager = $unitOfWork->objectManager;
        $classMetadataRegistry = $objectManager->classMetadataRegistry;
        $mapper = $objectManager->documentMapper;

        // Process upserts, grouped by index
        $states = new WeakMap();
        $documentsByIndex = [];
        foreach ($unitOfWork->getPendingUpserts() as $object) {
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $context = new MappingContext($classMetadata, $objectManager, $object, []);
            $document = $mapper->objectToDocument($object, [], $context);
            $states[$object] = $document;
            $documentsByIndex[$this->indexUid($classMetadata)][] = $document;
        }
        foreach ($documentsByIndex as $index => $documents) {
            foreach (iterable_chunk($documents, $flushBatchSize) as $chunk) {
                $tasks[] = $this->meili->index((string) $index)->updateDocuments([...$chunk]);
            }
        }

        // Process deletions, grouped by index
        $idsByIndex = [];
        $primaryKeys = [];
        foreach ($unitOfWork->getPendingDeletes() as $object) {
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $index = $this->indexUid($classMetadata);
            $primaryKeys[$index] = $classMetadata->getIdPropertyMetadata()->fieldName;
            $idsByIndex[$index][] = $classMetadataRegistry->getIdFromObject($object);
        }
        foreach ($idsByIndex as $index => $ids) {
            foreach (iterable_chunk($ids, $flushBatchSize) as $chunk) {
                $tasks[] = $this->meili->index((string) $index)->deleteDocuments([
                    'filter' => (string) field($primaryKeys[$index])->isIn(array_values([...$chunk])),
                ]);
            }
        }

        if ($options['wait']) {
            $this->meili->waitForTasks(
                array_column($tasks, 'taskUid'),
                $options['flushTimeoutMs'],
                $options['flushCheckIntervalMs'],
            );
        }

        foreach ($states as $object => $document) {
            $objectManager->identities->rememberState($object, $document);
        }
        $objectManager->identities->detach(...$unitOfWork->getPendingDeletes());
    }

    public function retrieveDocuments(AsDocument $classMetadata, Criteria $criteria): DocumentResultset|SearchResultset
    {
        return $this->retrieve($this->criteriaCompiler->compile($classMetadata, $criteria));
    }

    /**
     * Retrieves documents from a native Meilisearch query.
     */
    public function retrieve(DocumentsCriteriaWrapper $criteria): DocumentResultset|SearchResultset
    {
        return match ($criteria->query instanceof SearchQuery) {
            true => new SearchResultset($this->meili, $criteria),
            false => new DocumentResultset($this->meili, $criteria),
        };
    }

    public function retrieveDocumentById(AsDocument $classMetadata, mixed $id): ?array
    {
        try {
            return $this->meili->index($this->indexUid($classMetadata))->getDocument($id);
        } catch (ApiException $e) {
            if (404 === $e->httpStatus) {
                return null;
            }

            throw $e; // @codeCoverageIgnore
        }
    }
}
