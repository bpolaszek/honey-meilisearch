<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\Filter;
use Honey\Criteria\Filter\SubstringFilter;
use Honey\Criteria\Filter\SubstringOperator;
use InvalidArgumentException;

use function Bentools\MeilisearchFilters\field;

final readonly class SubstringFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof SubstringFilter;
    }

    /**
     * @param SubstringFilter $filter
     */
    public function parse(
        Filter $filter,
        FilterParser $mainParser,
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
