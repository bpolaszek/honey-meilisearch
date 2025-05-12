<?php

namespace Honey\MeilisearchAdapter\Schema;

use Closure;
use Exception;
use Honey\Odm\Config\AsAttribute as AttributeMetatada;
use Honey\Odm\Manager\ClassMetadataRegistry;
use Meilisearch\Client;

use function array_values;
use function BenTools\IterableFunctions\iterable;
use function Honey\Odm\uniqueList;

final readonly class SchemaUpdater
{
    public function __construct(
        private Client $meili,
        private ClassMetadataRegistry $registry,
    ) {
    }

    public function updateSchema(?Closure $onProgress = null): void
    {
        $onProgress ??= fn () => null;
        foreach ($this->registry->storage as $class => $metadata) {
            $task = $this->meili->createIndex($metadata->bucketName, ['primaryKey' => $metadata->primaryKey]);
            $this->meili->waitForTask($task['taskUid']);
            $shouldBeFilterableAttributes = [
                $metadata->primaryKey,
                ...iterable(array_values($metadata->properties))
                    ->filter(function (AttributeMetatada $attribute) {
                    return null !== $attribute->relation
                        || true === $attribute->filterable;
                })
                    ->map(fn (AttributeMetatada $attr) => $attr->attributeName ?? $attr->property->getName())
            ];
            $shouldBeSortableAttributes = [
                $metadata->primaryKey,
                ...iterable(array_values($metadata->properties))
                    ->filter(function (AttributeMetatada $attribute) {
                    return true === $attribute->sortable;
                })
                    ->map(fn (AttributeMetatada $attr) => $attr->attributeName ?? $attr->property->getName())
            ];
            $existingFilterableAttributes = $this->meili->index($metadata->bucketName)->getFilterableAttributes();
            $task = $this->meili->index($metadata->bucketName)->updateFilterableAttributes(
                uniqueList([...$existingFilterableAttributes, ...$shouldBeFilterableAttributes])->toArray()
            );
            $this->meili->waitForTask($task['taskUid']);
            $existingSortableAttributes = $this->meili->index($metadata->bucketName)->getSortableAttributes();
            $task = $this->meili->index($metadata->bucketName)->updateSortableAttributes(
                uniqueList([...$existingSortableAttributes, ...$shouldBeSortableAttributes])->toArray()
            );
            $this->meili->waitForTask($task['taskUid']);
            $onProgress($class, $metadata);
        }
    }

    public function dropSchema(?Closure $onProgress = null): void
    {
        $onProgress ??= fn () => null;
        foreach ($this->registry->storage as $class => $metadata) {
            if (!$this->indexExists($metadata->bucketName)) {
                goto Next;
            }
            $task = $this->meili->deleteIndex($metadata->bucketName);
            $this->meili->waitForTask($task['taskUid']);
            Next:
            $onProgress($class, $metadata);
        }
    }

    private function indexExists(string $indexUid): bool
    {
        try {
            $this->meili->getIndex($indexUid);
        } catch (Exception) {
            return false;
        }

        return true;
    }
}
