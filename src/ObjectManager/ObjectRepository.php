<?php

namespace Honey\MeilisearchAdapter\ObjectManager;

use Honey\MeilisearchAdapter\Misc\LazySearchResult;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Sort\SortInterface;
use Honey\Odm\Manager\ObjectRepositoryInterface;
use Stringable;

use function array_map;
use function Honey\Odm\resolveFilters;
use function Honey\Odm\resolveSorts;

final readonly class ObjectRepository implements ObjectRepositoryInterface
{
    public function __construct(
        private ObjectManager $objectManager,
        private string $className,
    ) {
    }

    public function findBy(
        Filter|array $filters = [],
        SortInterface|array $sort = [],
        int $limit = 0,
        int $offset = 0,
        array $params = [],
    ): LazySearchResult {
        $classMetadata = $this->objectManager->getClassMetadata($this->className);
        $filters = array_map(
            fn (Filter $filter) => $this->objectManager->filterConverter->convert($filter),
            resolveFilters($filters),
        );
        $sort = array_map(
            fn (SortInterface $sort) => $this->objectManager->sortConverter->convert($sort),
            resolveSorts($sort),
        );

        return new LazySearchResult(
            $this->objectManager->meili,
            $classMetadata->bucketName,
            '',
            [
                'filter' => array_map('strval', $filters),
                'sort' => array_map('strval', $sort),
                'limit' => $limit,
                'offset' => $offset,
                ...$params,
            ],
            fn (array $document) => $this->objectManager->factory($document, $classMetadata),
        );
    }

    public function findOneBy(Filter|array $filters = [], SortInterface|array $sort = [], array $params = []): ?object
    {
        $results = [...$this->findBy($filters, $sort, 1)];

        return $results[0] ?? null;
    }

    public function find(Stringable|int|string $id, array $params = []): ?object
    {
        $classMetadata = $this->objectManager->getClassMetadata($this->className);
        $object = $this->objectManager->loadedObjects->getObject($id, $this->className);

        if ($object !== null) {
            return $object;
        }

        return $this->findOneBy([$classMetadata->idProperty => $id]);
    }
}
