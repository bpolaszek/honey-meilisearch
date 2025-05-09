<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Converter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\AttributeConverter\AttributeConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\SubstringFilter;
use Honey\Odm\Criteria\Filter\SubstringOperator;
use InvalidArgumentException;

use function Bentools\MeilisearchFilters\field;

final readonly class SubstringFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof SubstringFilter;
    }

    /**
     * @param SubstringFilter $filter
     */
    public function convert(
        Filter $filter,
        FilterConverters $filterConverters,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        $expression = match ($filter->operator) {
            SubstringOperator::CONTAINS => field($attribute)->contains($filter->value),
            SubstringOperator::STARTS_WITH => field($attribute)->startsWith($filter->value),
            default => throw new InvalidArgumentException('Invalid operator'),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
