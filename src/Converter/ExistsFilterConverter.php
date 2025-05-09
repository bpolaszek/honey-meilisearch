<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Converter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\AttributeConverter\AttributeConverterInterface;
use Honey\Odm\Criteria\Filter\ExistsFilter;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;

use function Bentools\MeilisearchFilters\field;

final readonly class ExistsFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof ExistsFilter;
    }

    /**
     * @param ExistsFilter $filter
     */
    public function convert(
        Filter $filter,
        FilterConverters $filterConverters,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $attribute = $attributeConverter->getAttribute($filter->attribute);
        $expression = field($attribute)->exists();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
