<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Sort;

use Honey\Odm\Misc\PrivateConstructor;
use Stringable;

use function sprintf;

final readonly class GeoPoint implements Stringable
{
    use PrivateConstructor;

    public float $latitude;
    public float $longitude;

    public function __toString(): string
    {
        return sprintf('_geoPoint(%s,%s)', $this->latitude, $this->longitude);
    }

    public static function make(
        float $latitude,
        float $longitude,
    ): self
    {
        $geoPoint = self::getPrototype();
        $geoPoint->latitude = $latitude;
        $geoPoint->longitude = $longitude;

        return $geoPoint;
    }
}
