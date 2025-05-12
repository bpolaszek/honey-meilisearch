<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\ExistsFilter;
use Honey\Odm\Criteria\Filter\Filter;

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
    public function convert(Filter $filter): Expression
    {
        $attribute = $filter->attribute;
        $expression = field($attribute)->exists();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
