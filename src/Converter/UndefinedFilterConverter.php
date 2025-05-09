<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Converter;

use Bentools\MeilisearchFilters\EmptyExpression;
use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\AttributeConverter\AttributeConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\UndefinedFilter;

final readonly class UndefinedFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof UndefinedFilter;
    }

    /**
     * @param UndefinedFilter $filter
     */
    public function convert(
        Filter $filter,
        FilterConverters $filterConverters,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        static $expression;

        return $expression ??= new EmptyExpression();
    }
}
