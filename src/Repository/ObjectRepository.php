<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Repository;

/**
 * @template O of object
 *
 * @implements ObjectRepositoryInterface<O>
 */
final readonly class ObjectRepository implements ObjectRepositoryInterface
{
    use ObjectRepositoryTrait;
}
