<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch;

use WeakMap;

use function BenTools\IterableFunctions\iterable_chunk;
use function in_array;

use const PHP_INT_MAX;

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
function getItemsByBatches(iterable $items, int $batchSize): iterable
{
    if (PHP_INT_MAX === $batchSize) {
        return [is_array($items) ? $items : iterator_to_array($items)];
    }

    return iterable_chunk($items, $batchSize);
}
