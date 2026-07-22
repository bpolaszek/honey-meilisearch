<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit;

use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Criteria\Comparison;
use Honey\ODM\Core\Criteria\CompositeExpression;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Criteria\ExpressionInterface;
use Honey\ODM\Core\Criteria\Operator;
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;
use Honey\ODM\Meilisearch\Criteria\CriteriaCompiler;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Book;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Contracts\SearchQuery;

use function expect;
use function Honey\ODM\Core\Criteria\field;
use function Honey\ODM\Core\Criteria\not;

describe('CriteriaCompiler', function () {
    $compiler = new CriteriaCompiler();
    $classMetadata = new ClassMetadataRegistry()->getClassMetadata(Book::class);

    it('compiles comparisons, mapping property names to field names', function (
        callable $expression,
        string $expectedFilter,
    ) use (
        $compiler,
        $classMetadata
) {
        $wrapper = $compiler->compile($classMetadata, Criteria::create()->where($expression()));

        expect($wrapper->index)->toBe('books')
            ->and($wrapper->query)->toBeInstanceOf(DocumentsQuery::class)
            ->and($wrapper->query?->toArray()['filter'])->toBe([$expectedFilter]);
    })->with([
        'equals' => [fn () => field('name')->equals('Dune'), "title = 'Dune'"],
        'notEquals' => [fn () => field('name')->notEquals('Dune'), "title != 'Dune'"],
        'greaterThan' => [fn () => field('id')->greaterThan(5), "id > '5'"],
        'greaterThanOrEquals' => [fn () => field('id')->greaterThanOrEquals(5), "id >= '5'"],
        'lessThan' => [fn () => field('id')->lessThan(5), "id < '5'"],
        'lessThanOrEquals' => [fn () => field('id')->lessThanOrEquals(5), "id <= '5'"],
        'in' => [fn () => field('language')->in(['spa', 'fre']), "language IN ['spa', 'fre']"],
        'notIn' => [fn () => field('language')->notIn(['spa', 'fre']), "language NOT IN ['spa', 'fre']"],
        'contains' => [fn () => field('name')->contains('Dune'), "title CONTAINS 'Dune'"],
        'startsWith' => [fn () => field('name')->startsWith('Dune'), "title STARTS WITH 'Dune'"],
        'isNull' => [fn () => field('language')->isNull(), 'language IS NULL'],
        'isNotNull' => [fn () => field('language')->isNotNull(), 'language IS NOT NULL'],
        'hasAll' => [fn () => field('language')->hasAll(['spa', 'fre']), "language = 'spa' AND language = 'fre'"],
        'exists' => [fn () => field('language')->exists(), 'language EXISTS'],
        'isEmpty' => [fn () => field('language')->isEmpty(), 'language IS EMPTY'],
        'between, both bounds inclusive' => [fn () => field('id')->between(1, 10), "id '1' TO '10'"],
        'between, left exclusive' => [
            fn () => field('id')->between(1, 10, includeLeft: false),
            "id > '1' AND id <= '10'",
        ],
        'between, right exclusive' => [
            fn () => field('id')->between(1, 10, includeRight: false),
            "id >= '1' AND id < '10'",
        ],
        'between, open-ended left' => [fn () => field('id')->between(null, 10), "id <= '10'"],
        'between, open-ended right' => [fn () => field('id')->between(1, null), "id >= '1'"],
        'withinGeoRadius' => [
            fn () => field('location')->withinGeoRadius(48.8566, 2.3522, 1500.4),
            '_geoRadius(48.8566, 2.3522, 1500)',
        ],
        'withinGeoBoundingBox' => [
            fn () => field('location')->withinGeoBoundingBox(48.8, 2.3, 48.9, 2.4),
            '_geoBoundingBox([48.9, 2.4], [48.8, 2.3])',
        ],
    ]);

    it('complains about ENDS_WITH, which Meilisearch cannot express', function () use ($compiler, $classMetadata) {
        $compiler->compile($classMetadata, Criteria::create()->where(field('name')->endsWith('Dune')));
    })->throws(UnsupportedExpressionException::class);

    it('complains when a geo comparison does not map to the reserved `_geo` field', function () use ($compiler, $classMetadata) {
        $compiler->compile($classMetadata, Criteria::create()->where(field('language')->withinGeoRadius(48.8566, 2.3522, 1000)));
    })->throws(UnsupportedExpressionException::class);

    it('complains when BETWEEN carries a value that is not a Range', function () use ($compiler, $classMetadata) {
        $compiler->compile($classMetadata, Criteria::create()->where(new Comparison('id', Operator::BETWEEN, 5)));
    })->throws(UnsupportedExpressionException::class);

    it('compiles composite expressions and negations', function () use ($compiler, $classMetadata) {
        $criteria = Criteria::create()
            ->where(
                field('language')->equals('spa'),
                not(field('name')->equals('Dune')),
            )
            ->orWhere(field('isbn')->isNull());

        $wrapper = $compiler->compile($classMetadata, $criteria);

        expect($wrapper->query?->toArray()['filter'])->toBe([
            "((language = 'spa') AND (NOT (title = 'Dune'))) OR (isbn13 IS NULL)",
        ]);
    });

    it('compiles single-expression composites without extra operators', function () use ($compiler, $classMetadata) {
        $criteria = Criteria::create()->where(CompositeExpression::and(field('language')->equals('spa')));

        $wrapper = $compiler->compile($classMetadata, $criteria);

        expect($wrapper->query?->toArray()['filter'])->toBe(["(language = 'spa')"]);
    });

    it('compiles sorts, offset and limit into a documents query', function () use ($compiler, $classMetadata) {
        $criteria = Criteria::create()
            ->orderBy('name', 'desc')
            ->orderBy('id')
            ->offset(10)
            ->limit(5);

        $wrapper = $compiler->compile($classMetadata, $criteria);
        $query = $wrapper->query?->toArray() ?? [];

        expect($wrapper->query)->toBeInstanceOf(DocumentsQuery::class)
            ->and($query['sort'])->toBe(['title:desc', 'id:asc'])
            ->and($query['offset'])->toBe(10)
            ->and($query['limit'])->toBe(5);
    });

    it('compiles search criteria into a search query', function () use ($compiler, $classMetadata) {
        $criteria = Criteria::create()
            ->search('dune')
            ->where(field('language')->equals('eng'))
            ->orderBy('name')
            ->offset(2)
            ->limit(3);

        $wrapper = $compiler->compile($classMetadata, $criteria);
        $query = $wrapper->query?->toArray() ?? [];

        expect($wrapper->query)->toBeInstanceOf(SearchQuery::class)
            ->and($query['q'])->toBe('dune')
            ->and($query['filter'])->toBe(["language = 'eng'"])
            ->and($query['sort'])->toBe(['title:asc'])
            ->and($query['offset'])->toBe(2)
            ->and($query['limit'])->toBe(3);
    });

    it('complains on unsupported expressions', function () use ($compiler, $classMetadata) {
        $unsupported = new class implements ExpressionInterface {
        };

        $compiler->compile($classMetadata, Criteria::create()->where($unsupported));
    })->throws(UnsupportedExpressionException::class);

    it('prepends the configured index prefix to the index uid', function () use ($classMetadata) {
        $prefixedCompiler = new CriteriaCompiler(indexPrefix: 'staging-');

        $wrapper = $prefixedCompiler->compile($classMetadata, Criteria::create());

        expect($wrapper->index)->toBe('staging-books');
    });
});
