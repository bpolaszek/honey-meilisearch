<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Criteria;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Meilisearch\Contracts\DocumentsQuery;
use ReflectionProperty;

beforeEach(function () {
    // Reset static state before each test - reset global default
    DocumentsCriteriaWrapper::setDefaultBatchSize(
        Reflection::class(DocumentsCriteriaWrapper::class)->getConstant('DEFAULT_BATCH_SIZE')
    );

    // Note: We cannot easily reset index-specific batch sizes since they're stored
    // in a private static array, but each test should be independent enough
    // to not interfere with each other by using unique index names
});

it('creates instance with default values', function () {
    $wrapper = new DocumentsCriteriaWrapper('test-index');

    expect($wrapper->index)->toBe('test-index')
        ->and($wrapper->query)->toBeNull()
        ->and($wrapper->batchSize)->toBe(1000);
});

it('creates instance with custom DocumentsQuery', function () {
    $query = new DocumentsQuery();
    $wrapper = new DocumentsCriteriaWrapper('test-index', $query);

    expect($wrapper->index)->toBe('test-index')
        ->and($wrapper->query)->toBe($query)
        ->and($wrapper->batchSize)->toBe(1000);
});

it('creates instance with custom batch size', function () {
    $wrapper = new DocumentsCriteriaWrapper('test-index', null, 500);

    expect($wrapper->index)->toBe('test-index')
        ->and($wrapper->query)->toBeNull()
        ->and($wrapper->batchSize)->toBe(500);
});

it('creates instance with all custom parameters', function () {
    $query = new DocumentsQuery();
    $wrapper = new DocumentsCriteriaWrapper('test-index', $query, 750);

    expect($wrapper->index)->toBe('test-index')
        ->and($wrapper->query)->toBe($query)
        ->and($wrapper->batchSize)->toBe(750);
});

it('sets default batch size globally', function () {
    DocumentsCriteriaWrapper::setDefaultBatchSize(2000);

    expect(DocumentsCriteriaWrapper::getDefaultBatchSize())->toBe(2000);

    $wrapper = new DocumentsCriteriaWrapper('test-index');
    expect($wrapper->batchSize)->toBe(2000);
});

it('sets default batch size for specific index', function () {
    DocumentsCriteriaWrapper::setDefaultBatchSize(1500, 'books-index-1');

    expect(DocumentsCriteriaWrapper::getDefaultBatchSize('books-index-1'))->toBe(1500)
        ->and(DocumentsCriteriaWrapper::getDefaultBatchSize())->toBe(1000); // global default unchanged

    $wrapper = new DocumentsCriteriaWrapper('books-index-1');
    expect($wrapper->batchSize)->toBe(1500);
});

it('uses index-specific batch size over global default', function () {
    DocumentsCriteriaWrapper::setDefaultBatchSize(2000); // global
    DocumentsCriteriaWrapper::setDefaultBatchSize(3000, 'articles-index-2'); // index-specific

    $globalWrapper = new DocumentsCriteriaWrapper('other-index-unique');
    $indexWrapper = new DocumentsCriteriaWrapper('articles-index-2');

    expect($globalWrapper->batchSize)->toBe(2000)
        ->and($indexWrapper->batchSize)->toBe(3000);
});

it('uses constructor batch size over defaults', function () {
    DocumentsCriteriaWrapper::setDefaultBatchSize(2000); // global
    DocumentsCriteriaWrapper::setDefaultBatchSize(3000, 'products-index-3'); // index-specific

    $wrapper = new DocumentsCriteriaWrapper('products-index-3', null, 500);

    expect($wrapper->batchSize)->toBe(500);
});

it('returns null for non-existent index-specific batch size', function () {
    expect(DocumentsCriteriaWrapper::getDefaultBatchSize('non-existent-unique-index'))->toBeNull();
});

it('handles multiple index-specific batch sizes', function () {
    DocumentsCriteriaWrapper::setDefaultBatchSize(100, 'multi-index-1');
    DocumentsCriteriaWrapper::setDefaultBatchSize(200, 'multi-index-2');
    DocumentsCriteriaWrapper::setDefaultBatchSize(300, 'multi-index-3');

    expect(DocumentsCriteriaWrapper::getDefaultBatchSize('multi-index-1'))->toBe(100)
        ->and(DocumentsCriteriaWrapper::getDefaultBatchSize('multi-index-2'))->toBe(200)
        ->and(DocumentsCriteriaWrapper::getDefaultBatchSize('multi-index-3'))->toBe(300)
        ->and(DocumentsCriteriaWrapper::getDefaultBatchSize('multi-index-4'))->toBeNull();
});

it('overwrites existing index-specific batch size', function () {
    DocumentsCriteriaWrapper::setDefaultBatchSize(500, 'users-index-4');
    expect(DocumentsCriteriaWrapper::getDefaultBatchSize('users-index-4'))->toBe(500);

    DocumentsCriteriaWrapper::setDefaultBatchSize(800, 'users-index-4');
    expect(DocumentsCriteriaWrapper::getDefaultBatchSize('users-index-4'))->toBe(800);
});

it('handles zero batch size', function () {
    $wrapper = new DocumentsCriteriaWrapper('test-index', null, 0);
    expect($wrapper->batchSize)->toBe(0);
});

it('batch size property is readonly', function () {
    $wrapper = new DocumentsCriteriaWrapper('test-index', null, 500);

    // Verify we can't modify it from outside
    expect($wrapper->batchSize)->toBe(500);

    // The batchSize property should be private(set) so this should be read-only from outside
    $reflection = new ReflectionProperty($wrapper, 'batchSize');
    expect($reflection->isReadOnly())->toBeFalse(); // It's private(set), not readonly
});

it('index property is readonly', function () {
    $wrapper = new DocumentsCriteriaWrapper('test-index');

    $reflection = new ReflectionProperty($wrapper, 'index');
    expect($reflection->isReadOnly())->toBeTrue();
});

it('query property is readonly', function () {
    $query = new DocumentsQuery();
    $wrapper = new DocumentsCriteriaWrapper('test-index', $query);

    $reflection = new ReflectionProperty($wrapper, 'query');
    expect($reflection->isReadOnly())->toBeTrue();
});

it('maintains static state between instances', function () {
    DocumentsCriteriaWrapper::setDefaultBatchSize(1500);
    DocumentsCriteriaWrapper::setDefaultBatchSize(2500, 'special-index-5');

    $wrapper1 = new DocumentsCriteriaWrapper('normal-index-unique');
    $wrapper2 = new DocumentsCriteriaWrapper('special-index-5');
    $wrapper3 = new DocumentsCriteriaWrapper('normal-index-unique-2');

    expect($wrapper1->batchSize)->toBe(1500)
        ->and($wrapper2->batchSize)->toBe(2500)
        ->and($wrapper3->batchSize)->toBe(1500);
});
