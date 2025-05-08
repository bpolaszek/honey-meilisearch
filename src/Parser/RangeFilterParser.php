<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\Filter;
use Honey\Criteria\Filter\RangeFilter;

use function Bentools\MeilisearchFilters\field;

final readonly class RangeFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof RangeFilter;
    }

    /**
     * @param RangeFilter $filter
     */
    public function parse(
        Filter $filter,
        FilterParser $mainParser,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        $expression = match (true) {
            $filter->includeLeft && $filter->includeRight => field($attribute)->isBetween(
                $filter->left,
                $filter->right,
                true,
            ),
            !$filter->includeLeft && !$filter->includeRight => field($attribute)->isBetween(
                $filter->left,
                $filter->right,
                false,
            ),
            $filter->includeLeft => field($attribute)
                ->isGreaterThan($filter->left, true)
                ->and(
                    field($attribute)->isLowerThan($filter->right),
                ),
            $filter->includeRight => field($attribute)
                ->isGreaterThan($filter->left)
                ->and(
                    field($attribute)->isLowerThan($filter->right, true),
                ),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
