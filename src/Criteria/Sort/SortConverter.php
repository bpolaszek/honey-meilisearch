<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Sort;

use Honey\Odm\Criteria\Sort\Converter\SortConverterInterface;
use Honey\Odm\Criteria\Sort\GeoDistanceSort;
use Honey\Odm\Criteria\Sort\SortInterface;
use Honey\Odm\Criteria\Sort\ValueSort;

final class SortConverter implements SortConverterInterface
{
    public function convert(SortInterface $sort): mixed
    {
        return match (true) {
            $sort instanceof ValueSort => Sort::make($sort->attribute, $sort->direction->value),
            $sort instanceof GeoDistanceSort => Sort::make(
                GeoPoint::make($sort->coordinates->latitude, $sort->coordinates->longitude),
                $sort->direction->value,
            ),
        };
    }

}
