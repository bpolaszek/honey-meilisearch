<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\FilterConverter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\AttributeConverter\AttributeConverterInterface;
use Honey\Odm\Criteria\Filter\CompositeFilter;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;
use Honey\Odm\Criteria\Filter\Satisfy;

use function Bentools\MeilisearchFilters\filterBuilder;

final readonly class CompositeFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof CompositeFilter;
    }

    /**
     * @param CompositeFilter $filter
     */
    public function convert(
        Filter $filter,
        FilterConverters $filterConverters,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $convertd = [];
        foreach ($filter as $subFilter) {
            $convertd[] = $filterConverters->convert($subFilter, $attributeConverter);
        }

        $expression = match ($filter->satisfies) {
            Satisfy::ALL => filterBuilder()->and(...$convertd),
            Satisfy::ANY => filterBuilder()->or(...$convertd),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
