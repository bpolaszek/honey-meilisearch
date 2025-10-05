<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Transport;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Misc\UniqueList;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteria;
use Meilisearch\Client;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Contracts\DocumentsResults;
use Meilisearch\Exceptions\ApiException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WeakMap;

use function array_column;
use function BenTools\IterableFunctions\iterable;
use function Bentools\MeilisearchFilters\field;
use function Honey\ODM\Meilisearch\getItemsByBatches;
use function Honey\ODM\Meilisearch\weakmap_values;

/**
 * @implements TransportInterface<DocumentsCriteria>
 */
final readonly class MeiliTransport implements TransportInterface
{
    public const array DEFAULT_OPTIONS = [
        'flushBatchSize' => PHP_INT_MAX,
        'flushTimeoutMs' => 900_000,
        'flushCheckIntervalMs' => 50,
    ];

    public array $options;

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
     * @param UnitOfWork<AsDocument, AsAttribute, DocumentsQuery> $unitOfWork
     * @return void
     */
    public function flushPendingOperations(UnitOfWork $unitOfWork): void
    {
        $tasks = [];
        $flushBatchSize = $this->options['flushBatchSize'];
        $objectManager = $unitOfWork->objectManager;
        $classMetadataRegistry = $objectManager->classMetadataRegistry;
        $mapper = $objectManager->documentMapper;

        // Process upserts
        $objectsIndex = new WeakMap();
        foreach ($unitOfWork->getPendingUpserts() as $object) {
            $metadata = $classMetadataRegistry->getClassMetadata($object::class);
            $objectsIndex[$object] = $metadata->index;
        }
        $updateIndexes = new UniqueList([...weakmap_values($objectsIndex)]);
        foreach ($updateIndexes as $index) {
            $objects = iterable($unitOfWork->getPendingUpserts())
                ->filter(fn (object $object) => $objectsIndex[$object] === $index);
            $documents = $objects->map(function (object $object) use ($mapper, $unitOfWork, $classMetadataRegistry) {
                $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
                $context = new MappingContext($classMetadata, $unitOfWork->objectManager, $object, []);

                return $mapper->objectToDocument($object, [], $context);
            });

            foreach (getItemsByBatches($documents, $flushBatchSize) as $documents) {
                $docs = [...$documents];
                $tasks[] = $this->meili->index($index)->updateDocuments($docs);
            }
        }

        // Process deletions
        $objectsIndex = new WeakMap();
        foreach ($unitOfWork->getPendingDeletes() as $object) {
            $metadata = $classMetadataRegistry->getClassMetadata($object::class);
            $objectsIndex[$object] = $metadata->index;
        }
        $deletionIndexes = new UniqueList([...weakmap_values($objectsIndex)]);
        foreach ($deletionIndexes as $index) {
            foreach (getItemsByBatches($unitOfWork->getPendingDeletes(), $flushBatchSize) as $objects) {
                $primaryKey = $metadata->getIdPropertyMetadata()->name
                    ?? $metadata->getIdPropertyMetadata()->reflection->name;
                $tasks[] = $this->meili->index($index)->deleteDocuments([
                    'filter' => (string) field($primaryKey)->isIn(
                        iterable($objects)
                            ->map(fn (object $object) => $classMetadataRegistry->getIdFromObject($object))
                            ->asArray(),
                    ),
                ]);
            }
        }

        $this->meili->waitForTasks(
            array_column($tasks, 'taskUid'),
            $this->options['flushTimeoutMs'],
            $this->options['flushCheckIntervalMs'],
        );
    }

    public function retrieveDocuments(mixed $criteria): DocumentsResults
    {
        return $this->meili->index($criteria->index)->getDocuments($criteria->getQuery());
    }

    public function retrieveDocumentById(ClassMetadataInterface $classMetadata, mixed $id): ?array
    {
        try {
            return $this->meili->index($classMetadata->index)->getDocument($id);
        } catch (ApiException $e) {
            if (404 === $e->httpStatus) {
                return null;
            }

            throw $e;
        }
    }

}
