<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Repository;

use Honey\ODM\Core\Repository\ObjectRepositoryInterface as BaseObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Meilisearch\Contracts\DocumentsQuery;

/**
 * @template O of object
 *
 * @extends  BaseObjectRepositoryInterface<DocumentsQuery|null, O>
 */
interface ObjectRepositoryInterface extends BaseObjectRepositoryInterface
{
    /**
     * @return ObjectResultset<O>
     */
    public function findBy(mixed $criteria): ObjectResultset;

    /**
     * @return ObjectResultset<O>
     */
    public function findAll(): ObjectResultset;
}
