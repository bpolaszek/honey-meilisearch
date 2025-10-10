<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Repository;

use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use InvalidArgumentException;
use Meilisearch\Contracts\DocumentsQuery;

use function get_debug_type;

/**
 * @template O of object
 *
 * @implements ObjectRepositoryInterface<O>
 */
final readonly class ObjectRepository implements ObjectRepositoryInterface
{
    /**
     * @param ObjectManager<AsDocument<object, AsAttribute>, AsAttribute, DocumentsCriteriaWrapper> $manager
     * @param class-string<O> $className
     */
    public function __construct(
        private ObjectManager $manager,
        private string $className,
    ) {
    }

    public function findBy(mixed $criteria): ObjectResultset
    {
        /** @var MeiliTransport $transport */
        $transport = $this->manager->transport;
        /** @var AsDocument<O, AsAttribute> $classMetadata */
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        $documentsQuery = $criteria instanceof DocumentsQuery ? $criteria : null;
        $documents = $transport->retrieveDocuments(new DocumentsCriteriaWrapper($classMetadata->index, $documentsQuery));

        return new ObjectResultset($this->manager, $documents, $classMetadata); // @phpstan-ignore return.type
    }

    public function findAll(): ObjectResultset
    {
        return $this->findBy(null);
    }

    /**
     * @return O|null
     */
    public function findOneBy(mixed $criteria): ?object
    {
        if (!$criteria instanceof DocumentsQuery) {
            throw new InvalidArgumentException(sprintf('Expected %s, got %s', DocumentsQuery::class, get_debug_type($criteria)));
        }

        $criteria = clone $criteria;
        $criteria->setLimit(1);

        return [...$this->findBy($criteria)][0] ?? null;
    }

    /**
     * @return O|null
     */
    public function find(mixed $id): ?object
    {
        /** @var MeiliTransport $transport */
        $transport = $this->manager->transport;
        /** @var AsDocument<O, AsAttribute> $classMetadata */
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        $mapper = $this->manager->documentMapper;

        $document = $transport->retrieveDocumentById($classMetadata, $id);
        if (null === $document) {
            return null;
        }

        return $this->manager->factory($document, $classMetadata);
    }
}
