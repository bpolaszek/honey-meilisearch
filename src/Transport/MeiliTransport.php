<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Transport;

use Honey\ODM\Core\Config\ClassMetadata;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Misc\UniqueList;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
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
use function assert;
use function BenTools\IterableFunctions\iterable;
use function Bentools\MeilisearchFilters\field;
use function Honey\ODM\Meilisearch\iterable_chunk;
use function Honey\ODM\Meilisearch\weakmap_values;

/**
 * @implements TransportInterface<DocumentsCriteriaWrapper>
 *
 * @phpstan-type MeiliTransportOptions array{flushBatchSize?: int, flushTimeoutMs?: int, flushCheckIntervalMs?: int}
 */
final readonly class MeiliTransport implements TransportInterface
{
    public const array DEFAULT_OPTIONS = [
        'flushBatchSize' => PHP_INT_MAX,
        'flushTimeoutMs' => 900_000,
        'flushCheckIntervalMs' => 50,
    ];

    /**
     * @var MeiliTransportOptions
     */
    public array $options;

    /**
     * @param MeiliTransportOptions $options
     */
    public function __construct(
        public Client $meili,
        array $options = [],
    ) {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefaults(self::DEFAULT_OPTIONS);
        $optionsResolver->setAllowedTypes('flushBatchSize', ['int']);
        $optionsResolver->setAllowedTypes('flushTimeoutMs', ['int']);
        $optionsResolver->setAllowedTypes('flushCheckIntervalMs', ['int']);
        $this->options = $optionsResolver->resolve($options);
    }

    /**
     * @param UnitOfWork<AsDocument<object, AsAttribute>, AsAttribute, DocumentsCriteriaWrapper> $unitOfWork
     */
    public function flushPendingOperations(UnitOfWork $unitOfWork): void // @phpstan-ignore method.childParameterType
    {
        $tasks = [];
        $flushBatchSize = $this->options['flushBatchSize'];
        $objectManager = $unitOfWork->objectManager;
        $classMetadataRegistry = $objectManager->classMetadataRegistry;
        $mapper = $objectManager->documentMapper;

        // Process upserts
        $objectsIndex = new WeakMap();
        $states = new WeakMap();
        foreach ($unitOfWork->getPendingUpserts() as $object) {
            /** @var AsDocument<object, AsAttribute> $metadata */
            $metadata = $classMetadataRegistry->getClassMetadata($object::class);
            $objectsIndex[$object] = $metadata->index;
        }
        $updateIndexes = new UniqueList();
        foreach (weakmap_values($objectsIndex) as $index) {
            $updateIndexes[] = $index;
        }
        foreach ($updateIndexes as $index) {
            $objects = iterable($unitOfWork->getPendingUpserts())
                ->filter(fn (object $object) => $objectsIndex[$object] === $index); // @phpstan-ignore identical.alwaysFalse
            $documents = $objects->map(function (object $object) use ($mapper, $unitOfWork, $classMetadataRegistry, $states) {
                $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
                $context = new MappingContext($classMetadata, $unitOfWork->objectManager, $object, []);
                $document = $mapper->objectToDocument($object, [], $context);
                $states[$object] = $document;

                return $document;
            });

            foreach (iterable_chunk($documents, $flushBatchSize) as $documents) {
                $docs = [...$documents];
                $tasks[] = $this->meili->index($index)->updateDocuments($docs);
            }
        }

        // Process deletions
        $objectsIndex = new WeakMap();
        $indexMetadataMap = [];
        foreach ($unitOfWork->getPendingDeletes() as $object) {
            /** @var AsDocument<object, AsAttribute> $metadata */
            $metadata = $classMetadataRegistry->getClassMetadata($object::class);
            $objectsIndex[$object] = $metadata->index;
            $indexMetadataMap[$metadata->index] = $metadata;
        }
        $deletionIndexes = new UniqueList();
        foreach (weakmap_values($objectsIndex) as $index) {
            $deletionIndexes[] = $index;
        }
        foreach ($deletionIndexes as $index) {
            $metadata = $indexMetadataMap[$index];
            foreach (iterable_chunk($unitOfWork->getPendingDeletes(), $flushBatchSize) as $objects) {
                $primaryKey = $metadata->getIdPropertyMetadata()->name
                    ?? $metadata->getIdPropertyMetadata()->reflection->name;
                $tasks[] = $this->meili->index($index)->deleteDocuments([
                    'filter' => (string) field($primaryKey)->isIn(
                        array_values(iterable($objects)
                            ->map(fn (object $object) => $classMetadataRegistry->getIdFromObject($object))
                            ->asArray()),
                    ),
                ]);
            }
        }

        $this->meili->waitForTasks(
            array_column($tasks, 'taskUid'),
            $this->options['flushTimeoutMs'],
            $this->options['flushCheckIntervalMs'],
        );

        foreach ($states as $object => $document) {
            $objectManager->identities->rememberState($object, $document);
        }
        $objectManager->identities->detach(...$unitOfWork->getPendingDeletes());
    }

    public function retrieveDocuments(mixed $criteria): DocumentResultset|SearchResultset
    {
        assert($criteria instanceof DocumentsCriteriaWrapper);

        return match ($criteria->query instanceof SearchQuery) {
            true => new SearchResultset($this->meili, $criteria),
            false => new DocumentResultset($this->meili, $criteria),
        };
    }

    /**
     * @param AsDocument<object, AsAttribute> $classMetadata
     */
    public function retrieveDocumentById(ClassMetadata $classMetadata, mixed $id): ?array
    {
        try {
            return $this->meili->index($classMetadata->index)->getDocument($id);
        } catch (ApiException $e) {
            if (404 === $e->httpStatus) {
                return null;
            }

            throw $e; // @codeCoverageIgnore
        }
    }
}
