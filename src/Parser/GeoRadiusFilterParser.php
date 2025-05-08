<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\Filter;
use Honey\Criteria\Filter\GeoRadiusFilter;
use InvalidArgumentException;

use function Bentools\MeilisearchFilters\withinGeoRadius;

final readonly class GeoRadiusFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof GeoRadiusFilter;
    }

    /**
     * @param GeoRadiusFilter $filter
     */
    public function parse(
        Filter $filter,
        FilterParser $mainParser,
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
