<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Criteria;

use Meilisearch\Contracts\DocumentsQuery;

final class DocumentsCriteriaWrapper
{
    private const int DEFAULT_BATCH_SIZE = 1000;
    private static int $defaultBatchSize = self::DEFAULT_BATCH_SIZE;

    /**
     * @var array<string, int>
     */
    private static array $batchSizeByIndex = [];
    private(set) int $batchSize;

    /**
     * @param non-negative-int $batchSize
     */
    public function __construct(
        public readonly string $index,
        public readonly ?DocumentsQuery $query = null,
        ?int $batchSize = null,
    ) {
        $this->batchSize = $batchSize ?? self::$batchSizeByIndex[$this->index] ?? self::$defaultBatchSize;
    }

    public static function setDefaultBatchSize(int $batchSize, ?string $index = null): void
    {
        if (null === $index) {
            self::$defaultBatchSize = $batchSize;

            return;
        }

        self::$batchSizeByIndex[$index] = $batchSize;
    }

    public static function getDefaultBatchSize(?string $index = null): ?int
    {
        return match ($index) {
            null => self::$defaultBatchSize,
            default => self::$batchSizeByIndex[$index] ?? null
        };
    }
}
