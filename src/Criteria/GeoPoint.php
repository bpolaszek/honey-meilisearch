<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Criteria;

use InvalidArgumentException;

use function array_map;
use function floatval;
use function ltrim;
use function sprintf;
use function str_starts_with;

final readonly class GeoPoint
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('_geoPoint(%s,%s)', $this->latitude, $this->longitude);
    }

    public static function fromString(string $string): self
    {
        if (!str_starts_with($string, '_geoPoint')) {
            throw new InvalidArgumentException('Invalid GeoPoint string format');
        }

        $trimmed = rtrim(ltrim($string, '_geoPoint('), ')');

        return new self(...array_map(floatval(...), explode(',', $trimmed)));
    }
}
