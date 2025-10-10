<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Result;

use Countable;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use IteratorAggregate;
use Traversable;
use WeakMap;

/**
 * @template O of object
 *
 * @implements IteratorAggregate<int, O>
 */
final class ObjectResultset implements IteratorAggregate, Countable
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
}
