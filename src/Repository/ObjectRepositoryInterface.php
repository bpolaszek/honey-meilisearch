<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Repository;

use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Repository\ObjectRepositoryInterface as BaseObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Contracts\SearchQuery;

/**
 * Accepts the platform-agnostic Criteria as well as native Meilisearch queries.
 *
 * @template O of object
 *
 * @extends BaseObjectRepositoryInterface<O>
 */
interface ObjectRepositoryInterface extends BaseObjectRepositoryInterface
{
    /**
     * @param Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array<string, mixed>|null $criteria
     *
     * @return ObjectResultset<O>
     */
    public function findBy(Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array|null $criteria): ObjectResultset;

    /**
     * @return ObjectResultset<O>
     */
    public function findAll(): ObjectResultset;

    /**
     * @param Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array<string, mixed> $criteria
     *
     * @return O|null
     */
    public function findOneBy(Criteria|DocumentsQuery|SearchQuery|DocumentsCriteriaWrapper|array $criteria): ?object;

    /**
     * @return O|null
     */
    public function find(mixed $id): ?object;
}
