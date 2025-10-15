<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Criteria;

use function sprintf;

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
}
