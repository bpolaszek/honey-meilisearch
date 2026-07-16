<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Repository;

use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Contracts\SearchQuery;

use function assert;
use function Honey\ODM\Meilisearch\index_uid;
use function is_array;

/**
 * @template O of object
 */
// @phpstan-ignore trait.unused
trait ObjectRepositoryTrait
{
    /**
     * @param class-string<O> $className
     */
    public function __construct(
        private readonly ObjectManager $manager,
        private readonly string $className,
    ) {
    }

    /**
     * @param Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array<string, mixed>|null $criteria
     *
     * @return ObjectResultset<O>
     */
    public function findBy(Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array|null $criteria): ObjectResultset
    {
        $classMetadata = $this->manager->getClassMetadata($this->className);
        $transport = $this->transport();
        $documents = match (true) {
            $criteria instanceof DocumentsCriteriaWrapper => $transport->retrieve($criteria),
            $criteria instanceof DocumentsQuery,
            $criteria instanceof SearchQuery => $transport->retrieve(
                new DocumentsCriteriaWrapper(index_uid($classMetadata), $criteria),
            ),
            default => $transport->retrieveDocuments($classMetadata, self::resolveCriteria($criteria)),
        };

        return new ObjectResultset($this->manager, $documents, $classMetadata);
    }

    /**
     * @return ObjectResultset<O>
     */
    public function findAll(): ObjectResultset
    {
        return $this->findBy(null);
    }

    /**
     * @param Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array<string, mixed> $criteria
     *
     * @return O|null
     */
    public function findOneBy(Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array $criteria): ?object
    {
        if ($criteria instanceof Criteria || is_array($criteria)) {
            $criteria = clone self::resolveCriteria($criteria);
            $criteria->limit(1);
        } else {
            ($criteria instanceof DocumentsCriteriaWrapper ? $criteria->query : $criteria)?->setLimit(1);
        }

        return [...$this->findBy($criteria)][0] ?? null;
    }

    /**
     * @return O|null
     */
    public function find(mixed $id): ?object
    {
        return $this->manager->find($this->className, $id);
    }

    private function transport(): MeiliTransport
    {
        $transport = $this->manager->transport;
        assert($transport instanceof MeiliTransport);

        return $transport;
    }

    /**
     * @param Criteria|array<string, mixed>|null $criteria
     */
    private static function resolveCriteria(Criteria|array|null $criteria): Criteria
    {
        return match (true) {
            $criteria instanceof Criteria => $criteria,
            default => Criteria::fromArray($criteria ?? []),
        };
    }
}
