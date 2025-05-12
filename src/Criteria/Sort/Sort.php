<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Sort;

use Honey\Odm\Misc\PrivateConstructor;
use Stringable;

use function sprintf;

final readonly class Sort implements Stringable
{
    use PrivateConstructor;

    public string|GeoPoint $field;
    public SortDirection $direction;

    public function __toString(): string
    {
        return sprintf('%s:%s', $this->field, $this->direction->value);
    }

    public static function make(
        string|GeoPoint $field,
        string|SortDirection $direction = SortDirection::ASC,
    ): self
    {
        $sort = self::getPrototype();
        $sort->field = $field;
        $sort->direction = $direction instanceof SortDirection ? $direction : SortDirection::from($direction);

        return $sort;
    }
}
