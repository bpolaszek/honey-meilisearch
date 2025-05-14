<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Config\AsDocument as ClassMetadata;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\NullFilter;
use Honey\Odm\Hydrater\HydraterInterface;

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
    public function convert(Filter $filter, ClassMetadata $classMetadata, HydraterInterface $hydrater): Expression
    {
        $attribute = $classMetadata->getAttributeMetadata($filter->attribute)->attributeName;
        $expression = field($attribute)->isNull();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
