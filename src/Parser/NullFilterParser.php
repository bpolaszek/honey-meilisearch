<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\Filter;
use Honey\Criteria\Filter\NullFilter;

use function Bentools\MeilisearchFilters\field;

final readonly class NullFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof NullFilter;
    }

    /**
     * @param NullFilter $filter
     */
    public function parse(
        Filter $filter,
        FilterParser $mainParser,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        $expression = field($attribute)->isNull();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
