<?php

namespace Honey\MeilisearchAdapter\ObjectManager;

use Honey\MeilisearchAdapter\Hydrater\PropertyTransformer\CoordinatesTransformer;
use Honey\MeilisearchAdapter\Hydrater\PropertyTransformer\DateTimeTransformer;
use Honey\MeilisearchAdapter\Hydrater\PropertyTransformer\ManyToOneRelationTransformer;
use Honey\Odm\Hydrater\Hydrater;
use Honey\Odm\Hydrater\HydraterInterface;
use Honey\Odm\Hydrater\PropertyTransformer\PropertyTransformerInterface;
use Honey\Odm\Hydrater\PropertyTransformer\StringableTransformer;
use Honey\Odm\Manager\ClassMetadataRegistry;
use Meilisearch\Client;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use WeakMap;

use function BenTools\IterableFunctions\iterable;
use function Bentools\MeilisearchFilters\field;
use function Honey\MeilisearchAdapter\getItemsByBatches;
use function Honey\MeilisearchAdapter\weakmap_values;
use function Honey\Odm\uniqueList;

final class ObjectManager extends \Honey\Odm\Manager\ObjectManager
{
    private const array DEFAULT_OPTIONS = [
        'flushBatchSize' => PHP_INT_MAX,
        'flushTimeoutMs' => 900_000,
        'flushCheckIntervalMs' => 50,
    ];

    public readonly array $options;

    public function __construct(
        public readonly Client $meili,
        public readonly PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
        ClassMetadataRegistry $classMetadataRegistry = new ClassMetadataRegistry(),
        EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        array $transformers = [new DateTimeTransformer(), new StringableTransformer(), new CoordinatesTransformer()],
        array $repositories = [],
        array $options = [],
    ) {
        $transformers = [...$transformers, new ManyToOneRelationTransformer($this)];
        $hydrater = new Hydrater($this->propertyAccessor, $transformers);
        parent::__construct($classMetadataRegistry, $eventDispatcher, $hydrater, $repositories);
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefaults(self::DEFAULT_OPTIONS);
        $optionsResolver->setAllowedTypes('flushBatchSize', ['int']);
        $optionsResolver->setAllowedTypes('flushTimeoutMs', ['int']);
        $optionsResolver->setAllowedTypes('flushCheckIntervalMs', ['int']);
        $this->options = $optionsResolver->resolve($options);
    }

    protected function doFlush(): void
    {
        $flushBatchSize = $this->options['flushBatchSize'];
        $this->unitOfWork->computeChangesets();

        CheckChangesetsAndFireEvents:
        $hash = $this->unitOfWork->hash;

        // Process Updates
        $updateMetadata = new WeakMap();
        $deleteMetadata = new WeakMap();
        foreach ($this->unitOfWork->changesets as $object => $changeset) {
            $metadata = $this->classMetadataRegistry->getClassMetadata($object::class);
            $updateMetadata[$object] = $metadata;
            $this->maybeFirePrePersistEvent($object);
            $this->maybeFirePreUpdateEvent($object);
        }
        foreach ($this->unitOfWork->removals as $object) {
            $metadata = $this->classMetadataRegistry->getClassMetadata($object::class);
            $deleteMetadata[$object] = $metadata;
            $this->maybeFirePreRemoveEvent($object);
        }

        // Check if changesets have changed during events
        $this->unitOfWork->computeChangesets();
        if ($this->unitOfWork->hash !== $hash) {
            goto CheckChangesetsAndFireEvents;
        }

        $tasks = [];
        foreach (uniqueList(weakmap_values($updateMetadata)) as $metadata) {
            $documents = iterable($this->unitOfWork->getChangedObjects())
                ->filter(fn (object $object) => $updateMetadata[$object] === $metadata)
                ->map(fn (object $object) => $this->unitOfWork->changesets[$object]->newDocument);

            foreach (getItemsByBatches($documents, $flushBatchSize) as $documents) {
                $docs = [...$documents];
                $tasks[] = $this->meili->index($metadata->indexUid)->updateDocuments($docs);
            }
        }

        // Process Deletions
        foreach (uniqueList(weakmap_values($deleteMetadata)) as $metadata) {
            $scheduledDeletions = iterable($this->unitOfWork->removals)
                ->filter(fn (object $object) => $deleteMetadata[$object] === $metadata);
            foreach (getItemsByBatches($scheduledDeletions, $flushBatchSize) as $objects) {
                $tasks[] = $this->meili->index($metadata->indexUid)->deleteDocuments([
                    'filter' => (string) field($metadata->primaryKey)->isIn(
                        iterable($objects)
                            ->map(function (object $object) {
                                $classMetadata = $this->getClassMetadata($object::class);

                                return $this->hydrater->getIdFromObject($object, $classMetadata);
                            })
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

        // Update identity map
        foreach ($this->unitOfWork->getChangedObjects() as $object) {
            $this->loadedObjects->rememberState($object, $this->unitOfWork->changesets[$object]->newDocument);
        }

        // Fire post-flush events
        $this->firePostFlushEvents();
    }
}
