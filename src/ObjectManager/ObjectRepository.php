<?php

namespace Honey\MeilisearchAdapter\ObjectManager;

use Honey\MeilisearchAdapter\Misc\LazySearchResult;
use Honey\Odm\Criteria\Filter\Filter;
use Honey\Odm\Criteria\Sort\SortInterface;
use Honey\Odm\Manager\ObjectRepositoryInterface;
use InvalidArgumentException;
use Stringable;

use function array_is_list;
use function array_map;
use function Honey\Odm\attr;
use function Honey\Odm\is_stringable;
use function Honey\Odm\resolveSorts;
use function Honey\Odm\throws;
use function is_array;
use function is_scalar;

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
            fn (Filter $filter) => $this->objectManager->filterConverter->convert(
                $filter,
                $classMetadata,
                $this->objectManager->hydrater,
            ),
            $this->resolveFilters($filters),
        );
        $sort = array_map(
            fn (SortInterface $sort) => $this->objectManager->sortConverter->convert($sort, $classMetadata),
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

    private function resolveFilters(Filter|array $filters): array
    {
        if ($filters instanceof Filter) {
            return [$filters];
        }

        if (!array_is_list($filters)) {
            $output = [];
            foreach ($filters as $attr => $value) {
                $value = $this->resolveFilterValue($value);
                $output[] = match (true) {
                    is_scalar($value) => attr($attr)->equals($value),
                    is_array($value) && array_is_list($value) => attr($attr)->includesAny($value),
                    default => throw new InvalidArgumentException("Filter value must be scalar or scalar list."),
                };
            }

            return $output;
        }

        return (fn (Filter ...$filters) => $filters)(...$filters);
    }

    private function resolveFilterValue(mixed $value): mixed
    {
        return match (true) {
            is_scalar($value) => $value,
            is_stringable($value) => (string) $value,
            is_array($value) && array_is_list($value) => array_map(__METHOD__, $value),
            is_object($value) && !throws(fn () => $this->objectManager->getClassMetadata($value::class),
            ) => $this->resolveFilterValue(
                $this->objectManager->hydrater->getIdFromObject(
                    $value,
                    $this->objectManager->getClassMetadata($value::class),
                ),
            ),
            default => throw new InvalidArgumentException("Filter value must be scalar or scalar list."),
        };
    }
}
