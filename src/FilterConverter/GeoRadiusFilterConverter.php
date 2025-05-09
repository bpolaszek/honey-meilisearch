<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\FilterConverter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\AttributeConverter\AttributeConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\GeoRadiusFilter;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use InvalidArgumentException;

use function Bentools\MeilisearchFilters\withinGeoRadius;

final readonly class GeoRadiusFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof GeoRadiusFilter;
    }

    /**
     * @param GeoRadiusFilter $filter
     */
    public function convert(
        Filter $filter,
        FilterConverters $filterConverters,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        if ('_geo' !== $attribute) {
            throw new InvalidArgumentException("GeoRadius Filter must be used with '_geo' attribute");
        }

        $expression = withinGeoRadius(
            $filter->coordinates->latitude,
            $filter->coordinates->longitude,
            $filter->distance,
        );

        if ($filter->isNegated()) {
            $expression = $expression->negate();
        }

        return $expression;
    }
}
