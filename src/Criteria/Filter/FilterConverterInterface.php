<?php

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Honey\Odm\Criteria\Filter\Filter;

interface FilterConverterInterface extends \Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface
{
    public function supports(Filter $filter): bool;
}
