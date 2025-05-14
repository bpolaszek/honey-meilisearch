<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Filter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Config\AsDocument as ClassMetadata;
use Honey\Odm\Criteria\Filter\CompositeFilter;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface as BaseFilterConverterInterface;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Filter\Satisfy;
use Honey\Odm\Hydrater\HydraterInterface;
use RuntimeException;

use function Bentools\MeilisearchFilters\filterBuilder;
use function sprintf;


final class DelegatingFilterConverter implements BaseFilterConverterInterface
{
    private array $converters;

    /**
     * @param FilterConverterInterface<Expression>[] $converters
     */
    public function __construct(
        array $converters = [
            new ComparisonFilterConverter(),
            new EmptyFilterConverter(),
            new ExistsFilterConverter(),
            new GeoBoundingBoxFilterConverter(),
            new GeoRadiusFilterConverter(),
            new NullFilterConverter(),
            new RangeFilterConverter(),
            new SubstringFilterConverter(),
            new UndefinedFilterConverter(),
        ],
    ) {
        $this->converters = $converters;
    }

    public function supports(Filter $filter): bool
    {
        return $filter instanceof CompositeFilter;
    }

    /**
     * @return Expression
     */
    public function convert(Filter $filter, ClassMetadata $classMetadata, HydraterInterface $hydrater): Expression
    {
        if ($filter instanceof CompositeFilter) {
            return $this->convertCompositeFilter($filter, $classMetadata, $hydrater);
        }

        foreach ($this->converters as $converter) {
            if ($converter->supports($filter)) {
                return $converter->convert($filter, $classMetadata, $hydrater);
            }
        }

        throw new RuntimeException(sprintf('No converter found for filter of type %s', $filter::class));
    }

    private function convertCompositeFilter(
        CompositeFilter $filter,
        ClassMetadata $classMetadata,
        HydraterInterface $hydrater,
    ): Expression {
        $converted = [];
        foreach ($filter as $subFilter) {
            $converted[] = $this->convert($subFilter, $classMetadata, $hydrater);
        }

        $expression = match ($filter->satisfies) {
            Satisfy::ALL => filterBuilder()->and(...$converted),
            Satisfy::ANY => filterBuilder()->or(...$converted),
        };

        return $filter->isNegated() ? $expression->negate() : $expression;
    }

}
