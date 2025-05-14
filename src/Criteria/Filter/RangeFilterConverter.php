<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Config\AsDocument as ClassMetadata;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\RangeFilter;

use Honey\Odm\Hydrater\HydraterInterface;

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
    public function convert(Filter $filter, ClassMetadata $classMetadata, HydraterInterface $hydrater): Expression
    {
        $attr = $classMetadata->getAttributeMetadata($filter->attribute);
        $left = $filter->left;
        $right = $filter->right;
        $attributeName = $attr->attributeName;

        $expression = match (true) {
            $filter->includeLeft && $filter->includeRight => field($attributeName)->isBetween(
                $left,
                $right,
                true,
            ),
            !$filter->includeLeft && !$filter->includeRight => field($attributeName)->isBetween(
                $left,
                $right,
                false,
            ),
            $filter->includeLeft => field($attributeName)
                ->isGreaterThan($left, true)
                ->and(
                    field($attributeName)->isLowerThan($right),
                ),
            $filter->includeRight => field($attributeName)
                ->isGreaterThan($left)
                ->and(
                    field($attributeName)->isLowerThan($right, true),
                ),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
