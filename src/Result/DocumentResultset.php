<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Result;

use Countable;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use IteratorAggregate;
use Meilisearch\Client;
use Meilisearch\Contracts\DocumentsQuery;
use Traversable;

use const PHP_INT_MAX;

final class DocumentResultset implements IteratorAggregate, Countable
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
        $documentsQuery = $this->criteria->query ?? new DocumentsQuery();

        return $this->iterate((clone $documentsQuery)->setLimit($this->criteria->batchSize));
    }

    private function iterate(DocumentsQuery $query, $i = 0): Traversable
    {
        $offset = $query->toArray()['offset'] ?? 0;
        $result = $this->meili->index($this->criteria->index)->getDocuments($query);
        $this->totalItems ??= $result->getTotal();

        foreach ($result as $item) {
            yield $item;
            ++$i;
            if ($i >= $this->limit) {
                return;
            }
        }

        $nextOffset = $offset + $this->criteria->batchSize;
        if ($nextOffset > $this->totalItems) {
            return;
        }

        foreach ($this->iterate($query->setOffset($nextOffset), $i) as $item) {
            yield $item;
        }
    }

    public function count(): int
    {
        return $this->totalItems ??= (function () {
            $query = clone ($this->criteria->query ?? new DocumentsQuery());
            $query->setLimit(0);

            return $this->meili->index($this->criteria->index)->getDocuments($query)->getTotal();
        })();
    }
}
