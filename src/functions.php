<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch;

use Honey\ODM\Core\Config\AsDocument;
use LogicException;
use WeakMap;

use function in_array;
use function sprintf;

use const PHP_INT_MAX;

/**
 * Returns the Meilisearch index UID for the given class metadata.
 *
 * @param AsDocument<object> $classMetadata
 */
function index_uid(AsDocument $classMetadata): string
{
    return $classMetadata->collection
        ?? throw new LogicException(sprintf('Class %s has no collection (index) name defined.', $classMetadata->className));
}

/**
 * @internal
 *
 * @template TKey of object
 * @template TValue
 *
 * @param WeakMap<TKey, TValue> $weakmap
 *
 * @return array<TValue>
 */
function weakmap_values(WeakMap $weakmap): array
{
    $values = [];
    foreach ($weakmap as $value) {
        if (!in_array($value, $values, true)) {
            $values[] = $value;
        }
    }

    return $values;
}

/**
 * @template T
 *
 * @param iterable<T> $items
 *
 * @return iterable<iterable<T>>
 */
function iterable_chunk(iterable $items, int $batchSize = PHP_INT_MAX): iterable
{
    if (PHP_INT_MAX === $batchSize) {
        return [is_array($items) ? $items : iterator_to_array($items)];
    }

    return \BenTools\IterableFunctions\iterable_chunk($items, $batchSize);
}
