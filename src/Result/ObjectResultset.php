<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Result;

use ArrayAccess;
use Countable;
use Honey\ODM\Core\Config\ClassMetadata;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use IteratorAggregate;
use RuntimeException;
use Traversable;
use WeakMap;

use function count;

/**
 * @template O of object
 *
 * @implements IteratorAggregate<int, O>
 * @implements ArrayAccess<int, O>
 */
final class ObjectResultset implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var WeakMap<O, array<string, array<string, array<string, mixed>>>>
     */
    public private(set) WeakMap $extra;

    /**
     * @param ObjectManager<AsDocument<O, AsAttribute>, AsAttribute, DocumentsCriteriaWrapper> $objectManager
     * @param ClassMetadata<O, AsAttribute> $classMetadata
     * @param list<array<string, mixed>>|(Traversable<int, array<string, mixed>>&Countable&ArrayAccess<int, array<string, mixed>>) $documents
     */
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly array|(Traversable&Countable&ArrayAccess) $documents,
        private readonly ClassMetadata $classMetadata,
    ) {
        $this->extra = new WeakMap();
    }

    public function getIterator(): Traversable
    {
        foreach ($this->documents as $document) {
            yield $this->factory($document);
        }
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return O
     */
    private function factory(array $document): object
    {
        $object = $this->objectManager->factory($document, $this->classMetadata);

        foreach ($document as $key => $value) {
            if (str_starts_with($key, '_')) {
                $key = substr($key, strlen('_'));
                $this->extra[$object] ??= [];
                $this->extra[$object][$key] = $value;
            }
        }

        return $object;
    }

    public function count(): int
    {
        return count($this->documents);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->documents[$offset]);
    }

    public function offsetGet(mixed $offset): ?object
    {
        $document = $this->documents[$offset];

        return match ($document) {
            null => null,
            default => $this->factory($document),
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('ArrayAccess on this object is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('ArrayAccess on this object is read-only.');
    }
}
