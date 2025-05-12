<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\EmptyExpression;
use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;
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
    public function convert(Filter $filter): Expression
    {
        static $expression;

        return $expression ??= new EmptyExpression();
    }
}
