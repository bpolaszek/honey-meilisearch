<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Criteria\Filter\ComparisonFilter;
use Honey\Odm\Criteria\Filter\ComparisonOperator;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;

use function Bentools\MeilisearchFilters\field;

final readonly class ComparisonFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof ComparisonFilter;
    }

    /**
     * @param ComparisonFilter $filter
     */
    public function convert(Filter $filter): Expression
    {
        $attribute = $filter->attribute;

        $expression = match ($filter->operator) {
            ComparisonOperator::EQUALS => field($attribute)->equals($filter->value),
            ComparisonOperator::NOT_EQUALS => field($attribute)->notEquals($filter->value),
            ComparisonOperator::LOWER_THAN => field($attribute)->isLowerThan($filter->value),
            ComparisonOperator::LOWER_THAN_OR_EQUALS => field($attribute)->isLowerThan($filter->value, true),
            ComparisonOperator::GREATER_THAN => field($attribute)->isGreaterThan($filter->value, true),
            ComparisonOperator::GREATER_THAN_OR_EQUALS => field($attribute)->isGreaterThan($filter->value, true),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
