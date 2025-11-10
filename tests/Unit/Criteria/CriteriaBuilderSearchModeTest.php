<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Criteria;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Meilisearch\Config\AsDocument as ClassMetadata;
use Honey\ODM\Meilisearch\Criteria\CriteriaBuilder;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Criteria\RetrievalMode;
use Honey\ODM\Meilisearch\Criteria\SortDirection;
use Meilisearch\Contracts\SearchQuery;

it('builds DocumentsCriteriaWrapper with SearchQuery and all options', function () {
    $classMetadata = new ClassMetadata('test-index');
    // Map property names to attribute names
    Reflection::property($classMetadata, 'propertiesMetadata')->setValue($classMetadata, [
        'title' => (object) ['name' => 'title_attr'],
        'priority' => (object) ['name' => 'priority_attr'],
    ]);

    $builder = new CriteriaBuilder($classMetadata, RetrievalMode::SEARCH);

    $builder->addFilter('status = "active"')
        ->addSort('priority', SortDirection::DESC)
        ->setFields(['id', 'title_attr'])
        ->setOffset(10)
        ->setLimit(25);

    $result = $builder->build(123);

    expect($result)->toBeInstanceOf(DocumentsCriteriaWrapper::class)
        ->and($result->index)->toBe('test-index')
        ->and($result->batchSize)->toBe(123)
        ->and($result->query)->toBeInstanceOf(SearchQuery::class);

    /** @var SearchQuery $query */
    $query = $result->query;

    // Assert filter
    $filters = Reflection::property($query, 'filter')->getValue($query);
    expect($filters)->toBe(['status = "active"']);

    // Assert sorts
    $sorts = Reflection::property($query, 'sort')->getValue($query);
    expect($sorts)->toBe(['priority_attr:desc']);

    // Assert attributesToRetrieve set from fields
    $attrs = Reflection::property($query, 'attributesToRetrieve')->getValue($query);
    expect($attrs)->toBe(['id', 'title_attr']);

    // Assert offset and limit
    $offset = Reflection::property($query, 'offset')->getValue($query);
    $limit = Reflection::property($query, 'limit')->getValue($query);
    expect($offset)->toBe(10)
        ->and($limit)->toBe(25);
});
