<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\ComparisonFilter;
use Honey\Criteria\Filter\ComparisonOperator;
use Honey\Criteria\Filter\Filter;

use function Bentools\MeilisearchFilters\field;

final readonly class ComparisonFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof ComparisonFilter;
    }

    public function parse(
        Filter $filter,
        FilterParser $mainParser,
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
