<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Misc;

use Closure;
use Honey\ODM\Core\Misc\ResultInterface;
use IteratorAggregate;
use Meilisearch\Client;
use Traversable;

use const PHP_INT_MAX;

/**
 * @internal
 */
final class LazySearchResult implements ResultInterface, IteratorAggregate
{
    private const int DEFAULT_DEFAULT_BATCH_SIZE = 1000;
    public static int $defaultBatchSize = self::DEFAULT_DEFAULT_BATCH_SIZE;
    private int $total;
    private int $hardLimit;
    private readonly array $searchParams;
    private int $batchSize;

    public function __construct(
        private readonly Client $meili,
        private readonly string $index,
        private readonly string $searchQuery = '',
        array $searchParams = [],
        private readonly ?Closure $transformer = null,
        ?int $batchSize = null,
    ) {
        $this->searchParams = $searchParams;
        $this->hardLimit = $searchParams['limit'] ?? PHP_INT_MAX;
        $this->batchSize = $batchSize ?? self::$defaultBatchSize;
    }

    public function getIterator(): Traversable
    {
        yield from $this->iterate($this->searchParams['offset'] ?? 0);
    }

    private function iterate(int $offset = 0, $i = 0): Traversable
    {
        $searchParams = [
            ...$this->searchParams,
            'limit' => $this->batchSize,
            'offset' => $offset,
        ];
        $result = $this->meili->index($this->index)->search($this->searchQuery, $searchParams);
        $this->total ??= $result->getEstimatedTotalHits();

        foreach ($result as $item) {
            yield $this->transformer ? ($this->transformer)($item) : $item;
            $i++;
            if ($i >= $this->hardLimit) {
                return;
            }
        }

        $nextOffset = $offset + $this->batchSize;
        if ($nextOffset > $this->total) {
            return;
        }

        foreach ($this->iterate($nextOffset, $i) as $item) {
            yield $item;
        }
    }

    public function count(): int
    {
        return $this->total ??= (function () {
            $searchParams = [...$this->searchParams, 'limit' => 0];
            $searchResult = $this->meili->index($this->index)->search($this->searchQuery, $searchParams);

            return $searchResult->getEstimatedTotalHits() - ($searchParams['offset'] ?? 0);
        })();
    }
}
