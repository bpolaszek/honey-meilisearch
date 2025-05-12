<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\RangeFilter;

use function Bentools\MeilisearchFilters\field;

final readonly class RangeFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof RangeFilter;
    }

    /**
     * @param RangeFilter $filter
     */
    public function convert(Filter $filter): Expression
    {
        $attribute = $filter->attribute;
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
