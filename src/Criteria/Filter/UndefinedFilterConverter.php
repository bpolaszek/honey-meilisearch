<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\EmptyExpression;
use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Config\AsDocument as ClassMetadata;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\UndefinedFilter;
use Honey\Odm\Hydrater\HydraterInterface;

final readonly class UndefinedFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof UndefinedFilter;
    }

    /**
     * @param UndefinedFilter $filter
     */
    public function convert(Filter $filter, ClassMetadata $classMetadata, HydraterInterface $hydrater): Expression
    {
        static $expression;

        return $expression ??= new EmptyExpression();
    }
}
