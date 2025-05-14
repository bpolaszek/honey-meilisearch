<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Config\AsDocument as ClassMetadata;
use Honey\Odm\Criteria\Filter\EmptyFilter;
use Honey\Odm\Criteria\Filter\Filter;

use function Bentools\MeilisearchFilters\field;

final readonly class EmptyFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof EmptyFilter;
    }

    /**
     * @param EmptyFilter $filter
     */
    public function convert(Filter $filter, ClassMetadata $classMetadata): Expression
    {
        $attribute = $classMetadata->getAttributeMetadata($filter->attribute)->attributeName;
        $expression = field($attribute)->isEmpty();

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
