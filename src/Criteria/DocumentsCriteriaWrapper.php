<?php

namespace Honey\ODM\Meilisearch\Criteria;

use Meilisearch\Contracts\DocumentsQuery;

final class DocumentsCriteriaWrapper
{
    private const int DEFAULT_BATCH_SIZE = 1000;

    public function __construct(
        public readonly string $index,
        public readonly DocumentsQuery|null $query = null,
        public int $batchSize = self::DEFAULT_BATCH_SIZE,
    ) {
    }
}
