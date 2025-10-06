<?php

namespace Honey\ODM\Meilisearch\Repository;

use Honey\ODM\Core\Repository\ObjectRepositoryInterface as BaseObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Result\DocumentResultset;
use Meilisearch\Contracts\DocumentsQuery;

/**
 * @template O of object
 * @extends  BaseObjectRepositoryInterface<DocumentsQuery, O>
 */
interface ObjectRepositoryInterface extends BaseObjectRepositoryInterface
{
    public function findBy(mixed $criteria): DocumentResultset;

    public function findAll(): DocumentResultset;
}
