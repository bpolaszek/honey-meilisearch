<?php

namespace Honey\ODM\Meilisearch\Repository;


use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use InvalidArgumentException;
use Meilisearch\Contracts\DocumentsQuery;

use function get_debug_type;

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

    public function findBy(mixed $criteria): ObjectResultset
    {
        /** @var MeiliTransport $transport */
        $transport = $this->manager->transport;
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        $documents = $transport->retrieveDocuments(new DocumentsCriteriaWrapper($classMetadata->index, $criteria));

        return new ObjectResultset($this->manager, $documents, $classMetadata);
    }

    public function findAll(): ObjectResultset
    {
        return $this->findBy(null);
    }

    public function findOneBy(mixed $criteria): ?object
    {
        if (!$criteria instanceof DocumentsQuery) {
            throw new InvalidArgumentException(
                sprintf('Expected %s, got %s', DocumentsQuery::class, get_debug_type($criteria)),
            );
        }

        $criteria = clone $criteria;
        $criteria->setLimit(1);

        return [...$this->findBy($criteria)][0] ?? null;
    }

    public function find(mixed $id): ?object
    {
        /** @var MeiliTransport $transport */
        $transport = $this->manager->transport;
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        $mapper = $this->manager->documentMapper;

        $document = $transport->retrieveDocumentById($classMetadata, $id);
        if (null === $document) {
            return null;
        }

        return $this->manager->factory($document, $classMetadata);
    }

}
