<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Criteria;

use Bentools\MeilisearchFilters\Field;
use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Misc\UniqueList;
use Honey\ODM\Meilisearch\Config\AsDocument as ClassMetadata;
use Honey\ODM\Meilisearch\Criteria\CriteriaBuilder;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Criteria\GeoPoint;
use Honey\ODM\Meilisearch\Criteria\SortDirection;
use Meilisearch\Contracts\DocumentsQuery;
use Stringable;

use const PHP_INT_MAX;

it('creates instance with correct initial state', function () {
    $builder = new CriteriaBuilder(new ClassMetadata('test-index'));

    expect($builder->filters)->toBeInstanceOf(UniqueList::class)
        ->and($builder->filters)->toHaveCount(0)
        ->and($builder->sorts)->toBeInstanceOf(UniqueList::class)
        ->and($builder->sorts)->toHaveCount(0);

    $reflection = Reflection::property($builder, 'fields');
    expect($reflection->getValue($builder))->toBeNull();

    $reflection = Reflection::property($builder, 'retrieveVectors');
    expect($reflection->getValue($builder))->toBeFalse();

    $reflection = Reflection::property($builder, 'offset');
    expect($reflection->getValue($builder))->toBe(0);

    $reflection = Reflection::property($builder, 'limit');
    expect($reflection->getValue($builder))->toBe(PHP_INT_MAX);
});

it('creates field from property name using class metadata', function () {
    $classMetadata = new ClassMetadata('test-index');
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'propertyName' => (object)['name' => 'attribute_name']
    ]);

    $builder = new CriteriaBuilder($classMetadata);
    $field = $builder->field('propertyName');

    expect($field)->toBeInstanceOf(Field::class);
});

it('adds string filter correctly', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);

    $result = $builder->addFilter('title = "test"');

    expect($result)->toBe($builder) // fluent interface
        ->and($builder->filters)->toHaveCount(1)
        ->and($builder->filters[0])->toBe('title = "test"');
});

it('adds stringable filter correctly', function () {
    $stringable = new class implements Stringable {
        public function __toString(): string
        {
            return 'category IN ["books", "movies"]';
        }
    };

    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);

    $result = $builder->addFilter($stringable);

    expect($result)->toBe($builder)
        ->and($builder->filters)->toHaveCount(1)
        ->and($builder->filters[0])->toBe('category IN ["books", "movies"]');
});

it('maintains unique filters in UniqueList', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);

    $builder->addFilter('title = "test"')
        ->addFilter('price > 10')
        ->addFilter('title = "test"'); // duplicate

    expect($builder->filters)->toHaveCount(2)
        ->and($builder->filters[0])->toBe('title = "test"')
        ->and($builder->filters[1])->toBe('price > 10');
});

it('adds sort with string direction', function () {
    $classMetadata = new ClassMetadata('test-index');
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'title' => (object)['name' => 'title_attr']
    ]);

    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->addSort('title', 'desc');

    expect($result)->toBe($builder)
        ->and($builder->sorts)->toHaveCount(1)
        ->and($builder->sorts[0])->toBe('title_attr:desc');
});

it('adds sort with SortDirection enum', function () {
    $classMetadata = new ClassMetadata('test-index');
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'price' => (object)['name' => 'price_attr']
    ]);

    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->addSort('price', SortDirection::ASC);

    expect($result)->toBe($builder)
        ->and($builder->sorts)->toHaveCount(1)
        ->and($builder->sorts[0])->toBe('price_attr:asc');
});

it('adds sort with default asc direction', function () {
    $classMetadata = new ClassMetadata('test-index');
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'created_at' => (object)['name' => 'created_at_attr']
    ]);

    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->addSort('created_at');

    expect($result)->toBe($builder)
        ->and($builder->sorts)->toHaveCount(1)
        ->and($builder->sorts[0])->toBe('created_at_attr:asc');
});

it('adds sort with GeoPoint', function () {
    $classMetadata = new ClassMetadata('test-index');
    $geoPoint = new GeoPoint(48.8566, 2.3522); // Paris coordinates

    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->addSort($geoPoint, 'asc');

    expect($result)->toBe($builder)
        ->and($builder->sorts)->toHaveCount(1)
        ->and($builder->sorts[0])->toBe('_geoPoint(48.8566,2.3522):asc');
});

it('adds sort with GeoPoint and SortDirection enum', function () {
    $classMetadata = new ClassMetadata('test-index');
    $geoPoint = new GeoPoint(-33.8688, 151.2093); // Sydney coordinates

    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->addSort($geoPoint, SortDirection::DESC);

    expect($result)->toBe($builder)
        ->and($builder->sorts)->toHaveCount(1)
        ->and($builder->sorts[0])->toBe('_geoPoint(-33.8688,151.2093):desc');
});

it('maintains unique sorts in UniqueList', function () {
    $classMetadata = new ClassMetadata('test-index');
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'title' => (object)['name' => 'title_attr'],
        'price' => (object)['name' => 'price_attr'],
    ]);

    $builder = new CriteriaBuilder($classMetadata);

    $builder->addSort('title', 'asc')
        ->addSort('price', 'desc')
        ->addSort('title', 'asc'); // duplicate

    expect($builder->sorts)->toHaveCount(2)
        ->and($builder->sorts[0])->toBe('title_attr:asc')
        ->and($builder->sorts[1])->toBe('price_attr:desc');
});

it('sets retrieve vectors flag', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);

    $result = $builder->setRetrieveVectors(true);

    expect($result)->toBe($builder);

    $reflection = Reflection::property($builder, 'retrieveVectors');
    expect($reflection->getValue($builder))->toBeTrue();
});

it('sets offset', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);

    $result = $builder->setOffset(100);

    expect($result)->toBe($builder);

    $reflection = Reflection::property($builder, 'offset');
    expect($reflection->getValue($builder))->toBe(100);
});

it('sets limit', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);

    $result = $builder->setLimit(50);

    expect($result)->toBe($builder);

    $reflection = Reflection::property($builder, 'limit');
    expect($reflection->getValue($builder))->toBe(50);
});

it('builds DocumentsCriteriaWrapper with no filters or sorts', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->build();

    expect($result)->toBeInstanceOf(DocumentsCriteriaWrapper::class)
        ->and($result->index)->toBe('test-index')
        ->and($result->query)->toBeInstanceOf(DocumentsQuery::class)
        ->and($result->batchSize)->toBe(1000);
});

it('builds DocumentsCriteriaWrapper with custom batch size', function () {
    $classMetadata = new ClassMetadata('test-index');

    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->build(500);

    expect($result)->toBeInstanceOf(DocumentsCriteriaWrapper::class)
        ->and($result->index)->toBe('test-index')
        ->and($result->batchSize)->toBe(500);
});

it('builds DocumentsCriteriaWrapper with filters', function () {
    $classMetadata = new ClassMetadata('test-index');

    $builder = new CriteriaBuilder($classMetadata);
    $builder->addFilter('price > 100')
        ->addFilter('category = "electronics"');

    $result = $builder->build();

    expect($result)->toBeInstanceOf(DocumentsCriteriaWrapper::class);

    // Verify the query has filters set
    $query = $result->query;
    $reflection = Reflection::property($query, 'filter');
    $filters = $reflection->getValue($query);

    expect($filters)->toBe(['price > 100', 'category = "electronics"']);
});

it('builds DocumentsCriteriaWrapper with sorts', function () {
    $classMetadata = new ClassMetadata('test-index');
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'title' => (object)['name' => 'title_attr'],
        'created_at' => (object)['name' => 'created_at_attr'],
    ]);

    $builder = new CriteriaBuilder($classMetadata);
    $builder->addSort('title', 'asc')
        ->addSort('created_at', 'desc');

    $result = $builder->build();

    expect($result)->toBeInstanceOf(DocumentsCriteriaWrapper::class);

    // Verify the query has sorts set
    $query = $result->query;
    $reflection = Reflection::property($query, 'sort');
    $sorts = $reflection->getValue($query);

    expect($sorts)->toBe(['title_attr:asc', 'created_at_attr:desc']);
});

it('builds DocumentsCriteriaWrapper with all options', function () {
    $classMetadata = new ClassMetadata('test-index');
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'priority' => (object)['name' => 'priority_attr']
    ]);

    $builder = new CriteriaBuilder($classMetadata);
    $builder->addFilter('status = "active"')
        ->addSort('priority', 'desc')
        ->setRetrieveVectors(true)
        ->setFields(['foo', 'bar'])
        ->setOffset(20)
        ->setLimit(100);

    $result = $builder->build(250);

    expect($result)->toBeInstanceOf(DocumentsCriteriaWrapper::class)
        ->and($result->index)->toBe('test-index')
        ->and($result->batchSize)->toBe(250);

    $query = $result->query;

    // Check offset
    $reflection = Reflection::property($query, 'offset');
    expect($reflection->getValue($query))->toBe(20);

    // Check limit
    $reflection = Reflection::property($query, 'limit');
    expect($reflection->getValue($query))->toBe(100);

    // Check retrieveVectors
    $reflection = Reflection::property($query, 'retrieveVectors');
    expect($reflection->getValue($query))->toBeTrue();

    // Check fields
    $reflection = Reflection::property($query, 'fields');
    expect($reflection->getValue($query))->toBe(['foo', 'bar']);
});

it('handles edge case with zero offset and limit', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);
    $builder->setOffset(0)->setLimit(0);

    $result = $builder->build();
    $query = $result->query;

    $offsetReflection = Reflection::property($query, 'offset');
    expect($offsetReflection->getValue($query))->toBe(0);

    $limitReflection = Reflection::property($query, 'limit');
    expect($limitReflection->getValue($query))->toBe(0);
});

it('handles large numbers for offset and limit', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);
    $builder->setOffset(PHP_INT_MAX - 1)->setLimit(PHP_INT_MAX);

    $result = $builder->build();
    $query = $result->query;

    $offsetReflection = Reflection::property($query, 'offset');
    expect($offsetReflection->getValue($query))->toBe(PHP_INT_MAX - 1);

    $limitReflection = Reflection::property($query, 'limit');
    expect($limitReflection->getValue($query))->toBe(PHP_INT_MAX);
});

it('handles extreme GeoPoint coordinates', function () {
    $classMetadata = new ClassMetadata('test-index');

    $builder = new CriteriaBuilder($classMetadata);

    // Test extreme valid coordinates
    $extremeGeoPoint = new GeoPoint(-90.0, -180.0);
    $result = $builder->addSort($extremeGeoPoint, 'asc');

    expect($result)->toBe($builder)
        ->and($builder->sorts)->toHaveCount(1)
        ->and($builder->sorts[0])->toBe('_geoPoint(-90,-180):asc');
});

it('restricts to certain fields', function () {
    $classMetadata = new ClassMetadata('test-index');

    $builder = new CriteriaBuilder($classMetadata);
    $builder->setFields(['id', 'name']);

    expect($builder->build()->query?->toArray()['fields'] ?? null)->toBe(['id', 'name']);
});

it('processes filters and sorts arrays correctly when empty', function () {
    $classMetadata = new ClassMetadata('test-index');
    $builder = new CriteriaBuilder($classMetadata);
    $result = $builder->build();

    $query = $result->query;

    // Verify empty filters don't get set
    $filterReflection = Reflection::property($query, 'filter');
    expect($filterReflection->getValue($query))->toBeNull();

    // Verify empty sorts don't get set
    $sortReflection = Reflection::property($query, 'sort');
    expect($sortReflection->getValue($query))->toBeNull();
});
