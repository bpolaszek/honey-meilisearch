<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\EmptyFilter;
use Honey\Criteria\Filter\Filter;

use function Bentools\MeilisearchFilters\field;

final readonly class EmptyFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof EmptyFilter;
    }

    /**
     * @param EmptyFilter $filter
     */
    public function parse(
        Filter $filter,
        FilterParser $mainParser,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        $expression = field($attribute)->isEmpty();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
