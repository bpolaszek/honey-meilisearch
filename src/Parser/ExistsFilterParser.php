<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\ExistsFilter;
use Honey\Criteria\Filter\Filter;

use function Bentools\MeilisearchFilters\field;

final readonly class ExistsFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof ExistsFilter;
    }

    /**
     * @param ExistsFilter $filter
     */
    public function parse(
        Filter $filter,
        FilterParser $mainParser,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        $expression = field($attribute)->exists();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
