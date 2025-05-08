<?php

namespace Honey\MeilisearchAdapter\Parser;

use Bentools\MeilisearchFilters\Expression;
use Honey\Criteria\AttributeConverter\AttributeConverter;
use Honey\Criteria\AttributeConverter\AttributeConverterInterface;
use Honey\Criteria\Filter\Filter;

final readonly class FilterParser
{
    /**
     * @var FilterParserInterface[]
     */
    private array $parsers;

    /**
     * @param FilterParserInterface[] $parsers
     */
    public function __construct(
        array $parsers = [
            new ComparisonFilterParser(),
            new CompositeFilterParser(),
            new EmptyFilterParser(),
            new ExistsFilterParser(),
            new GeoBoundingBoxFilterParser(),
            new GeoRadiusFilterParser(),
            new NullFilterParser(),
            new RangeFilterParser(),
            new SubstringFilterParser(),
            new UndefinedFilterParser(),
        ],
    ) {
        $this->parsers = (fn (FilterParserInterface ...$parsers) => $parsers)(...$parsers);
    }

    public function parseFilter(
        Filter $filter,
        AttributeConverterInterface $attributeConverter = new AttributeConverter(),
    ): Expression {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($filter)) {
                return $parser->parse($filter, $this, $attributeConverter);
            }
        }
    }
}
