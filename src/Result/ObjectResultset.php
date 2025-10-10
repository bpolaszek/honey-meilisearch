<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Result;

use ArrayAccess;
use Countable;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use InvalidArgumentException;
use IteratorAggregate;
use RuntimeException;
use Traversable;
use WeakMap;

use function count;
use function is_int;

/**
 * @template O of object
 *
 * @implements IteratorAggregate<int, O>
 */
final class ObjectResultset implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var WeakMap<O, array<string, mixed>>
     */
    public private(set) WeakMap $geo;

    /**
     * @var WeakMap<O, array<string, mixed>>
     */
    public private(set) WeakMap $vectors;

    /**
     * @param ObjectManager<AsDocument<O, AsAttribute>, AsAttribute, DocumentsCriteriaWrapper> $objectManager
     * @param ClassMetadataInterface<O, AsAttribute> $classMetadata
     */
    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly DocumentResultset $documents,
        private readonly ClassMetadataInterface $classMetadata,
    ) {
        $this->geo = new WeakMap();
        $this->vectors = new WeakMap();
    }

    public function getIterator(): Traversable
    {
        foreach ($this->documents as $document) {
            $object = $this->objectManager->factory($document, $this->classMetadata);
            if (isset($document['_geo'])) {
                $this->geo[$object] = $document['_geo'];
            }
            if (isset($document['_vectors'])) {
                $this->vectors[$object] = $document['_vectors'];
            }
            yield $object;
        }
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
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Offset must be an integer');
        }

        if ($offset >= count($this)) {
            return null;
        }

        foreach ($this as $i => $item) {
            if ($i === $offset) {
                return $item;
            }
        }

        return null;
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
