<?php

namespace Honey\MeilisearchAdapter\Converter;

use Bentools\MeilisearchFilters\Expression;
use Honey\Odm\Criteria\Filter\Converter\FilterConverters;
use Honey\Odm\Criteria\Filter\Converter\FilterConverterInterface;

/**
 * @implements FilterConverters<Expression>
 */
final class MeiliFilterConverter extends FilterConverters
{
    /**
     * @param FilterConverterInterface[] $converters
     */
    public function __construct(
        array $converters = [
            new ComparisonFilterConverter(),
            new CompositeFilterConverter(),
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
        parent::__construct($converters);
    }
}
