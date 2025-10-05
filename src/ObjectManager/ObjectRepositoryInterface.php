<?php

namespace Honey\ODM\Meilisearch\ObjectManager;

use Honey\ODM\Core\Repository\ObjectRepositoryInterface as BaseObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteria;

/**
 * @template O of object
 * @extends  BaseObjectRepositoryInterface<DocumentsCriteria, O>
 */
interface ObjectRepositoryInterface extends BaseObjectRepositoryInterface
{

}
