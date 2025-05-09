<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\FilterConverter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\AttributeConverter\AttributeConverterInterface;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\NullFilter;

use function Bentools\MeilisearchFilters\field;

final readonly class NullFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof NullFilter;
    }

    /**
     * @param NullFilter $filter
     */
    public function convert(
        Filter $filter,
        FilterConverters $filterConverters,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        $expression = field($attribute)->isNull();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
