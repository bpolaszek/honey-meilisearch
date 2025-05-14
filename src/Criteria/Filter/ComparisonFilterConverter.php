<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Config\AsDocument as ClassMetadata;
use Honey\Odm\Criteria\Filter\ComparisonFilter;
use Honey\Odm\Criteria\Filter\ComparisonOperator;
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
    public function convert(Filter $filter, ClassMetadata $classMetadata): Expression
    {
        $attr = $classMetadata->getAttributeMetadata($filter->attribute);
        $value = $filter->value;

        $expression = match ($filter->operator) {
            ComparisonOperator::EQUALS => field($attr->attributeName)->equals($value),
            ComparisonOperator::NOT_EQUALS => field($attr->attributeName)->notEquals($value),
            ComparisonOperator::LOWER_THAN => field($attr->attributeName)->isLowerThan($value),
            ComparisonOperator::LOWER_THAN_OR_EQUALS => field($attr->attributeName)->isLowerThan($value, true),
            ComparisonOperator::GREATER_THAN => field($attr->attributeName)->isGreaterThan($value, true),
            ComparisonOperator::GREATER_THAN_OR_EQUALS => field($attr->attributeName)->isGreaterThan($value, true),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
