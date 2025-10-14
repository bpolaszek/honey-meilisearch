<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Criteria;

use Bentools\MeilisearchFilters\Field;
use Honey\ODM\Core\Misc\UniqueList;
use Honey\ODM\Meilisearch\Config\AsDocument as ClassMetadata;
use Meilisearch\Contracts\DocumentsQuery;
use Stringable;

use function Bentools\MeilisearchFilters\field;
use function sprintf;

use const PHP_INT_MAX;

final class CriteriaBuilder
{
    /**
     * @var UniqueList<string>
     */
    public private(set) UniqueList $filters;

    /**
     * @var UniqueList<string>
     */
    public private(set) UniqueList $sorts;

    /**
     * @var string[]|null
     */
    private ?array $fields = null;

    private bool $retrieveVectors = false;
    private int $offset = 0;
    private int $limit = PHP_INT_MAX;

    public function __construct(
        private readonly ClassMetadata $classMetadata,
    ) {
        $this->filters = new UniqueList();
        $this->sorts = new UniqueList();
    }

    public function field(string $propertyName): Field
    {
        $attributeName = $this->classMetadata->getAttributeName($propertyName);

        return field($attributeName);
    }

    public function addFilter(string|Stringable $filter): self
    {
        $this->filters[] = (string) $filter;

        return $this;
    }

    public function addSort(string|GeoPoint $sortBy, string|SortDirection $direction = 'asc'): self
    {
        if ($direction instanceof SortDirection) {
            $direction = $direction->value;
        } else {
            $direction = SortDirection::from($direction)->value;
        }

        $sortBy = match ($sortBy instanceof GeoPoint) {
            true => (string) $sortBy,
            default => $this->classMetadata->getAttributeName($sortBy),
        };

        $this->sorts[] = sprintf('%s:%s', $sortBy, $direction);

        return $this;
    }

    public function setFields(?array $fields): CriteriaBuilder
    {
        $this->fields = $fields;

        return $this;
    }

    public function setRetrieveVectors(bool $retrieveVectors): CriteriaBuilder
    {
        $this->retrieveVectors = $retrieveVectors;

        return $this;
    }

    public function setOffset(int $offset): CriteriaBuilder
    {
        $this->offset = $offset;

        return $this;
    }

    public function setLimit(int $limit): CriteriaBuilder
    {
        $this->limit = $limit;

        return $this;
    }

    public function build(?int $batchSize = null): DocumentsCriteriaWrapper
    {
        $query = new DocumentsQuery();
        if (isset($this->filters[0])) {
            $query = $query->setFilter($this->filters->toArray());
        }
        if (isset($this->sorts[0])) {
            $query = $query->setSort($this->sorts->toArray());
        }
        if (isset($this->fields)) {
            $query = $query->setFields($this->fields);
        }

        $query = $query->setOffset($this->offset);
        $query = $query->setLimit($this->limit);
        $query = $query->setRetrieveVectors($this->retrieveVectors);

        return new DocumentsCriteriaWrapper(
            $this->classMetadata->index,
            $query,
            $batchSize,
        );
    }
}
