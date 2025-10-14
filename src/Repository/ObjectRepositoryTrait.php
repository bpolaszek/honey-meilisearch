<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Repository;

use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Criteria\CriteriaBuilder;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use InvalidArgumentException;
use Meilisearch\Contracts\DocumentsQuery;

use function is_array;

/**
 * @template O of object
 *
 * @implements ObjectRepositoryInterface<O>
 */
trait ObjectRepositoryTrait
{
    /**
     * @param ObjectManager<AsDocument<object, AsAttribute>, AsAttribute, DocumentsCriteriaWrapper> $manager
     * @param class-string<O> $className
     */
    public function __construct(
        private readonly ObjectManager $manager,
        private readonly string $className,
    ) {
    }

    public function findBy(mixed $criteria): ObjectResultset
    {
        $criteria = $this->resolveCriteria($criteria);

        /** @var MeiliTransport $transport */
        $transport = $this->manager->transport;
        /** @var AsDocument<O, AsAttribute> $classMetadata */
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        $documents = $transport->retrieveDocuments($criteria);

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
        $criteria = $this->resolveCriteria($criteria);
        $criteria->query->setLimit(1);

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

        $document = $transport->retrieveDocumentById($classMetadata, $id);
        if (null === $document) {
            return null;
        }

        return $this->manager->factory($document, $classMetadata);
    }

    public function createCriteriaBuilder(): CriteriaBuilder
    {
        /** @var AsDocument $classMetadata */
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);

        return new CriteriaBuilder($classMetadata);
    }

    private function resolveCriteria(mixed $criteria): DocumentsCriteriaWrapper
    {
        /** @var AsDocument $classMetadata */
        $classMetadata = $this->manager->classMetadataRegistry->getClassMetadata($this->className);
        if (null === $criteria) {
            return new DocumentsCriteriaWrapper($classMetadata->index);
        }
        if ($criteria instanceof DocumentsCriteriaWrapper) {
            return $criteria;
        }
        if ($criteria instanceof DocumentsQuery) {
            return new DocumentsCriteriaWrapper($classMetadata->index, $criteria);
        }
        if (is_array($criteria)) {
            $builder = $this->createCriteriaBuilder();
            foreach ($criteria as $key => $value) {
                $builder->addFilter($builder->field($key)->equals($value));
            }

            return $builder->build();
        }

        throw new InvalidArgumentException('Invalid criteria.');
    }
}
