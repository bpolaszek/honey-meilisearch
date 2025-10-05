<?php

namespace Honey\ODM\Meilisearch\ObjectManager;


use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;

use Countable;
use Meilisearch\Contracts\DocumentsResults;
use Traversable;

use function array_map;

/**
 * @template O of object
 * @implements ObjectRepositoryInterface<O>
 */
final readonly class ObjectRepository implements ObjectRepositoryInterface
{
    public function __construct(
        private ObjectManager $manager,
        private string $className,
    ) {
    }

    public function findBy(mixed $criteria): DocumentsResults
    {
        /** @var MeiliTransport $transport */
        $transport = $this->manager->transport;
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        $documents = clone $transport->retrieveDocuments($criteria);
        $documents['results'] = array_map(
            fn (array $document) => $this->manager->factory($document, $classMetadata), $documents->getResults()
        );

        return $documents;
    }

    public function findAll(): Traversable&Countable
    {
        // TODO: Implement findAll() method.
    }

    public function findOneBy(mixed $criteria): ?object
    {
        // TODO: Implement findOneBy() method.
    }

    public function find(mixed $id): ?object
    {
        // TODO: Implement find() method.
    }

}
