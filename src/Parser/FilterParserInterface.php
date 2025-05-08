<?php

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\Filter;

interface FilterParserInterface
{
    public function supports(Filter $filter): bool;

    public function parse(
        Filter $filter,
        FilterParser $mainParser,
        AttributeConverterInterface $attributeConverter,
    ): Expression;
}
