<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Result;

use Countable;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManager;
use IteratorAggregate;
use Traversable;
use WeakMap;

final class ObjectResultset implements IteratorAggregate, Countable
{
    public private(set) WeakMap $geo;
    public private(set) WeakMap $vectors;

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
