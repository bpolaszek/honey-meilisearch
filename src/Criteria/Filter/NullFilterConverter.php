<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
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
    public function convert(Filter $filter): Expression
    {
        $attribute = $filter->attribute;
        $expression = field($attribute)->isNull();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
