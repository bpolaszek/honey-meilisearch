<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Converter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\AttributeConverter\AttributeConverterInterface;
use Honey\Odm\Criteria\Filter\ComparisonFilter;
use Honey\Odm\Criteria\Filter\ComparisonOperator;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;

use function Bentools\MeilisearchFilters\field;

final readonly class ComparisonFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof ComparisonFilter;
    }

    public function convert(
        Filter $filter,
        FilterConverters $filterConverters,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);

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
