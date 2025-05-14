<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Config\AsDocument as ClassMetadata;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\SubstringFilter;
use Honey\Odm\Criteria\Filter\SubstringOperator;
use InvalidArgumentException;

use function Bentools\MeilisearchFilters\field;

final readonly class SubstringFilterConverter implements FilterConverterInterface
{
    public function supports(Filter $filter): bool
    {
        return $filter instanceof SubstringFilter;
    }

    /**
     * @param SubstringFilter $filter
     */
    public function convert(Filter $filter, ClassMetadata $classMetadata): Expression
    {
        $attr = $classMetadata->getAttributeMetadata($filter->attribute);
        $attributeName = $attr->attributeName;
        $value = $filter->substring;
        $expression = match ($filter->operator) {
            SubstringOperator::CONTAINS => field($attributeName)->contains($value),
            SubstringOperator::STARTS_WITH => field($attributeName)->startsWith($value),
            default => throw new InvalidArgumentException('Invalid operator'),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }
}
