<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Schema;

use Closure;
use Exception;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Misc\UniqueList;
use Honey\ODM\Meilisearch\Config\Attribute;
use Meilisearch\Client;

use function array_values;
use function BenTools\IterableFunctions\iterable;
use function Honey\ODM\Meilisearch\index_uid;

final readonly class SchemaUpdater
{
    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        private Client $meili,
        private ClassMetadataRegistry $registry,
    ) {
    }

    public function updateSchema(?Closure $onProgress = null): void
    {
        $onProgress ??= fn () => null;
        foreach ($this->registry as $class => $metadata) {
            $index = index_uid($metadata);
            $primaryKey = $metadata->getIdPropertyMetadata()->fieldName;
            $task = $this->meili->createIndex($index, ['primaryKey' => $primaryKey]);
            $this->meili->waitForTask($task['taskUid']);
            $shouldBeFilterableAttributes = [
                $primaryKey,
                ...iterable(array_values($metadata->propertiesMetadata))
                    ->filter(fn (AsField $field) => true === $field->getPlatformMetadata(Attribute::class)?->filterable)
                    ->map(fn (AsField $field) => $field->fieldName),
            ];
            $shouldBeSortableAttributes = [
                $primaryKey,
                ...iterable(array_values($metadata->propertiesMetadata))
                    ->filter(fn (AsField $field) => true === $field->getPlatformMetadata(Attribute::class)?->sortable)
                    ->map(fn (AsField $field) => $field->fieldName),
            ];
            /** @var string[] $existingFilterableAttributes */
            $existingFilterableAttributes = $this->meili->index($index)->getFilterableAttributes();
            $task = $this->meili->index($index)->updateFilterableAttributes(
                new UniqueList([...$existingFilterableAttributes, ...$shouldBeFilterableAttributes])->toArray() // @phpstan-ignore argument.type
            );
            $this->meili->waitForTask($task['taskUid']);
            $existingSortableAttributes = $this->meili->index($index)->getSortableAttributes();
            $task = $this->meili->index($index)->updateSortableAttributes(
                new UniqueList([...$existingSortableAttributes, ...$shouldBeSortableAttributes])->toArray() // @phpstan-ignore argument.type
            );
            $this->meili->waitForTask($task['taskUid']);
            $onProgress($class, $metadata);
        }
    }

    public function dropSchema(?Closure $onProgress = null): void
    {
        $onProgress ??= fn () => null;
        foreach ($this->registry as $class => $metadata) {
            $index = index_uid($metadata);
            if (!$this->indexExists($index)) {
                goto Next; // @codeCoverageIgnore
            }
            $task = $this->meili->deleteIndex($index);
            $this->meili->waitForTask($task['taskUid']);
            Next:
            $onProgress($class, $metadata);
        }
    }

    /**
     * @codeCoverageIgnore
     */
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
