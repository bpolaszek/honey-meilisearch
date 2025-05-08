<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\CompositeFilter;
use Honey\Criteria\Filter\Filter;
use Honey\Criteria\Filter\Satisfy;

use function Bentools\MeilisearchFilters\filterBuilder;

final readonly class CompositeFilterParser implements FilterParserInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof CompositeFilter;
    }

    /**
     * @param CompositeFilter $filter
     */
    public function parse(
        Filter $filter,
        FilterParser $mainParser,
        AttributeConverterInterface $attributeConverter,
    ): Expression {
        $parsed = [];
        foreach ($filter as $subFilter) {
            $parsed[] = $mainParser->parseFilter($subFilter);
        }

        $expression = match ($filter->satisfies) {
            Satisfy::ALL => filterBuilder()->and(...$parsed),
            Satisfy::ANY => filterBuilder()->or(...$parsed),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
