<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Coordinates;
use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\GeoBoundingBoxFilter;
use InvalidArgumentException;

use function Bentools\MeilisearchFilters\withinGeoBoundingBox;

final readonly class GeoBoundingBoxFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof GeoBoundingBoxFilter;
    }

    /**
     * @param GeoBoundingBoxFilter $filter
     */
    public function convert(Filter $filter): Expression
    {
        $attribute = $filter->attribute;
        if ('_geo' !== $attribute) {
            throw new InvalidArgumentException("GeoRadius Filter must be used with '_geo' attribute");
        }

        $expression = withinGeoBoundingBox(
            Coordinates::from($filter->boundingBox->topLeft->toArray()),
            Coordinates::from($filter->boundingBox->bottomRight->toArray()),
        );

        if ($filter->isNegated()) {
            $expression = $expression->negate();
        }

        return $expression;
    }
}
