<?php

namespace Honey\ODM\Meilisearch\Criteria;

use Meilisearch\Contracts\DocumentsQuery;

final readonly class DocumentsCriteria
{
    public function __construct(
        public string $index,
        public ?int $offset = null,
        public ?int $limit = null,
        public array|null $filter = null,
        public ?array $sort = null,
        public ?array $fields = null,
        public ?bool $retrieveVectors = null,
        public ?array $ids = null,
    ) {
    }

    public function getQuery(): DocumentsQuery
    {
        return new DocumentsQuery()
            ->setFields($this->fields)
            ->setFilter($this->filter)
            ->setOffset($this->offset)
            ->setLimit($this->limit)
            ->setSort($this->sort)
            ->setRetrieveVectors($this->retrieveVectors)
            ->setIds($this->ids);
    }
}
