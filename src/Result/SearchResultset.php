<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Result;

use ArrayAccess;
use Countable;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use InvalidArgumentException;
use IteratorAggregate;
use Meilisearch\Client;
use Meilisearch\Contracts\SearchQuery;
use RuntimeException;
use Traversable;

use function count;
use function is_int;

use const PHP_INT_MAX;

/**
 * @implements IteratorAggregate<int, array<string, mixed>>
 * @implements ArrayAccess<int, array<string, mixed>>
 */
final class SearchResultset implements IteratorAggregate, Countable, ArrayAccess
{
    public private(set) int $totalItems;
    private int $limit;

    public function __construct(
        private readonly Client $meili,
        private readonly DocumentsCriteriaWrapper $criteria,
    ) {
        $this->limit = $this->criteria->query?->toArray()['limit'] ?? PHP_INT_MAX;
    }

    public function getIterator(): Traversable
    {
        $query = $this->criteria->query ?? new SearchQuery();
        $offset = $query->toArray()['offset'] ?? 0;
        $i = 0;

        NextIteration:
        $batchQuery = (clone $query)->setOffset($offset);
        $result = $this->meili->index($this->criteria->index)->search(null, $batchQuery->toArray());
        $this->totalItems ??= $result->getEstimatedTotalHits() ?? PHP_INT_MAX;
        $hits = $result->getHits();
        if (!isset($hits[0])) {
            return;
        }

        foreach ($hits as $item) {
            yield $item;
            ++$i;
            if ($i >= $this->limit) {
                return;
            }
        }

        $offset += $this->criteria->batchSize;
        if ($offset > $this->totalItems) {
            return;
        }
        goto NextIteration;
    }

    public function count(): int
    {
        // @phpstan-ignore return.type
        return $this->totalItems ??= (function () {
            $query = clone ($this->criteria->query ?? new SearchQuery());
            $query->setLimit(0);

            return $this->meili
                ->index($this->criteria->index)
                ->search(null, $query->toArray())
                ->getEstimatedTotalHits() ?? PHP_INT_MAX;
        })();
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset < count($this);
    }

    public function offsetGet(mixed $offset): ?array
    {
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Offset must be an integer');
        }

        if ($offset >= count($this)) {
            return null;
        }

        foreach ($this as $i => $item) {
            if ($i === $offset) {
                return $item;
            }
        }

        return null; // @codeCoverageIgnore
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('ArrayAccess on this object is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('ArrayAccess on this object is read-only.');
    }
}
