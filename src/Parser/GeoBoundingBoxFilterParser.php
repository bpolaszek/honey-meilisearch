<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Coordinates;
use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\Filter;
use Honey\Criteria\Filter\GeoBoundingBoxFilter;
use InvalidArgumentException;

use function Bentools\MeilisearchFilters\withinGeoBoundingBox;

final readonly class GeoBoundingBoxFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof GeoBoundingBoxFilter;
    }

    /**
     * @param GeoBoundingBoxFilter $filter
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
