<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Schema;

use Closure;
use Exception;
use Honey\ODM\Core\Misc\UniqueList;
use Honey\ODM\Meilisearch\Config\AsAttribute as AttributeMetatada;
use Honey\ODM\Meilisearch\Config\ClassMetadataRegistry;
use Meilisearch\Client;

use function array_values;
use function BenTools\IterableFunctions\iterable;

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
            $primaryKey = $metadata->getIdPropertyMetadata()->name ?? $metadata->getIdPropertyMetadata()->reflection->name;
            $task = $this->meili->createIndex($metadata->index, ['primaryKey' => $primaryKey]);
            $this->meili->waitForTask($task['taskUid']);
            $shouldBeFilterableAttributes = [
                $primaryKey,
                ...iterable(array_values($metadata->propertiesMetadata))
                    ->filter(fn (AttributeMetatada $attribute) => true === $attribute->filterable)
                    ->map(fn (AttributeMetatada $attr) => $attr->name ?? $attr->reflection->name),
            ];
            $shouldBeSortableAttributes = [
                $primaryKey,
                ...iterable(array_values($metadata->propertiesMetadata))
                    ->filter(fn (AttributeMetatada $attribute) => true === $attribute->sortable)
                    ->map(fn (AttributeMetatada $attr) => $attr->name ?? $attr->reflection->name),
            ];
            /** @var string[] $existingFilterableAttributes */
            $existingFilterableAttributes = $this->meili->index($metadata->index)->getFilterableAttributes();
            $task = $this->meili->index($metadata->index)->updateFilterableAttributes(
                new UniqueList([...$existingFilterableAttributes, ...$shouldBeFilterableAttributes])->toArray() // @phpstan-ignore argument.type
            );
            $this->meili->waitForTask($task['taskUid']);
            $existingSortableAttributes = $this->meili->index($metadata->index)->getSortableAttributes();
            $task = $this->meili->index($metadata->index)->updateSortableAttributes(
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
            if (!$this->indexExists($metadata->index)) {
                goto Next; // @codeCoverageIgnore
            }
            $task = $this->meili->deleteIndex($metadata->index);
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
