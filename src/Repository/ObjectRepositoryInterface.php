<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Repository;

use Honey\ODM\Core\Repository\ObjectRepositoryInterface as BaseObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Criteria\CriteriaBuilder;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Meilisearch\Contracts\DocumentsQuery;

/**
 * @template O of object
 *
 * @extends  BaseObjectRepositoryInterface<DocumentsQuery|DocumentsCriteriaWrapper|array<string, mixed>|null, O>
 */
interface ObjectRepositoryInterface extends BaseObjectRepositoryInterface
{
    /**
     * @return ObjectResultset<O>
     */
    public function findBy(mixed $criteria): ObjectResultset;

    /**
     * @return O|null
     */
    public function findOneBy(mixed $criteria): ?object;

    /**
     * @return ObjectResultset<O>
     */
    public function findAll(): ObjectResultset;

    /**
     * @return O|null
     */
    public function find(mixed $id): ?object;

    /**
     * @return CriteriaBuilder
     */
    public function createCriteriaBuilder(): CriteriaBuilder;
}
