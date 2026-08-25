<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Integration;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Manager\Identities;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\ObjectManagerFactory;
use Honey\ODM\Meilisearch\Result\DocumentResultset;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Book;
use InvalidArgumentException;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Contracts\SearchQuery;
use RuntimeException;
use TypeError;

use function afterAll;
use function array_column;
use function beforeAll;
use function dirname;
use function file_get_contents;
use function Honey\ODM\Core\Criteria\field;
use function Honey\ODM\Core\Criteria\not;
use function Honey\ODM\Meilisearch\Tests\meili;
use function is_array;

beforeAll(function () {
    $tasks = [];
    $tasks[] = meili()->deleteIndex('authors');
    $tasks[] = meili()->deleteIndex('books');
    $tasks[] = meili()->createIndex('authors', ['primaryKey' => 'author_id']);
    $tasks[] = meili()->createIndex('books');
    meili()->waitForTasks(array_column($tasks, 'taskUid'));
    $tasks[] = meili()->index('authors')->addDocumentsJson(file_get_contents(dirname(__DIR__) . '/fixtures/authors.json'));
    $tasks[] = meili()->index('books')->addDocumentsJson(file_get_contents(dirname(__DIR__) . '/fixtures/books.json'));
    $tasks[] = meili()->index('books')->updateSortableAttributes(['id', 'author', 'publisher']);
    $tasks[] = meili()->index('books')->updateFilterableAttributes(['id', 'author', 'publisher', 'language', 'isbn13']);
    meili()->waitForTasks(array_column($tasks, 'taskUid'));
});

afterAll(function () {
    $tasks = [];
    $tasks[] = meili()->deleteIndex('authors');
    $tasks[] = meili()->deleteIndex('books');
    meili()->waitForTasks(array_column($tasks, 'taskUid'));
});

it('retrieves all books', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    DocumentsCriteriaWrapper::setDefaultBatchSize(100, 'books');
    $repository = $objectManager->getRepository(Book::class);
    $allBooks = $repository->findAll();
    expect($allBooks)->toBeInstanceOf(ObjectResultset::class)
        ->and($allBooks)->toHaveCount(164)
        ->and($allBooks)->each->toBeInstanceOf(Book::class)
        ->and(isset($allBooks[0]))->toBe(true)
        ->and(isset($allBooks[1_000_000]))->toBe(false)
        ->and($allBooks[0])->toBeInstanceOf(Book::class)
        ->and($allBooks[1_000_000])->toBeNull()
        ->and(fn () => $allBooks['foo'])->toThrow(InvalidArgumentException::class)
        ->and(fn () => $allBooks[0] = 'foo')->toThrow(RuntimeException::class)
        ->and(function () use ($allBooks) {
            unset($allBooks[0]);
        })->toThrow(RuntimeException::class)
        ;
});

it('does not silently skip documents when a batch has fewer results than the batch size (issue #12)', function () {
    // The books index holds 164 documents, well beyond Meilisearch's default
    // page size of 20. A batch size of 1000 means the very first batch query
    // (offset=0, no limit set) returns only 20 documents from Meilisearch,
    // yet the offset then jumps to 1000, which is past totalItems — silently
    // skipping every document beyond the first page.
    $resultset = new DocumentResultset(meili(), new DocumentsCriteriaWrapper('books', batchSize: 1000));

    expect(iterator_to_array($resultset))->toHaveCount(164);
});

it('uses native filters', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);

    $query = new DocumentsQuery();
    $books = $repository->findBy($query->setFilter(['language = spa']));
    expect($books)->toHaveCount(3);
});

it('filters with the generic criteria', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);

    $spanishBooks = $repository->findBy(Criteria::create()->where(field('language')->equals('spa')));
    expect($spanishBooks)->toHaveCount(3);

    $spanishOrFrenchBooks = $repository->findBy(Criteria::create()->where(field('language')->in(['spa', 'fre'])));
    expect($spanishOrFrenchBooks)->toHaveCount(5);

    $sameButComposed = $repository->findBy(
        Criteria::create()
            ->where(field('language')->equals('spa'))
            ->orWhere(field('language')->equals('fre')),
    );
    expect($sameButComposed)->toHaveCount(5);

    $frenchBooksAgain = $repository->findBy(
        Criteria::create()->where(
            field('language')->in(['spa', 'fre']),
            not(field('language')->equals('spa')),
        ),
    );
    expect($frenchBooksAgain)->toHaveCount(2);
});

it('sorts and paginates with the generic criteria', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);

    $ascending = [...$repository->findBy(Criteria::create()->orderBy('id')->limit(3))];
    $descending = [...$repository->findBy(Criteria::create()->orderBy('id', 'desc')->limit(3))];
    $shifted = [...$repository->findBy(Criteria::create()->orderBy('id')->offset(1)->limit(3))];

    expect($ascending)->toHaveCount(3)
        ->and($descending)->toHaveCount(3)
        ->and($ascending[0]->id)->not->toBe($descending[0]->id)
        ->and($shifted[0]->id)->toBe($ascending[1]->id);
});

it('searches with the generic criteria', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);

    $book = $repository->findOneBy(
        Criteria::create()
            ->search('chamber of secrets')
            ->where(field('language')->equals('eng')),
    );
    expect($book)->toBeInstanceOf(Book::class)
        ->and($book->name)->toContain('Chamber of Secrets');
});

it('finds a specific book by its id', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);
    /** @var Book $book */
    $book = $repository->find(619);
    expect($book)->toBeInstanceOf(Book::class)
        ->and($book->id)->toBe(619)
        ->and($book->isbn)->toBe('9780439786190')
    ;
});

it('returns null when the document does not exist', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);
    /** @var Book $book */
    $book = $repository->find(1337);
    expect($book)->toBeNull();
});

it('finds a specific book using filters', function (mixed $criteria) {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);
    /** @var Book $book */
    $book = $repository->findOneBy($criteria);
    expect($book)->toBeInstanceOf(Book::class)
        ->and($book->isbn)->toBe('9780439786184')
    ;
})->with(function () {
    yield 'array' => [['isbn' => '9780439786184']];
    yield 'Criteria' => [Criteria::create()->where(field('isbn')->equals('9780439786184'))];
    yield 'DocumentsQuery' => [new DocumentsQuery()->setFilter(['isbn13 = 9780439786184'])];
    yield 'SearchQuery' => [new SearchQuery()->setFilter(['isbn13 = 9780439786184'])];
    yield 'DocumentsCriteriaWrapper' => [new DocumentsCriteriaWrapper('books', new DocumentsQuery()->setFilter(['isbn13 = 9780439786184']))];
});

it('complains when criteria are not of the expected type', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $repository = $objectManager->getRepository(Book::class);
    $repository->findOneBy(new \stdClass()); // @phpstan-ignore argument.type
})->throws(TypeError::class);

it('persists stuff', function () {
    $objectManager = ObjectManagerFactory::create(meili());
    $book = $objectManager->find(Book::class, 4);
    assert($book instanceof Book);

    $rememberedStates = Reflection::property(Identities::class, 'rememberedStates')->getValue($objectManager->identities);
    $initialDocument = $rememberedStates[$book];
    assert(is_array($initialDocument));

    // When
    $objectManager->remove($book);
    $objectManager->flush(['flushBatchSize' => 10]);

    // Then
    expect($objectManager->find(Book::class, 4))->toBeNull();

    // When
    $book = $objectManager->factory($initialDocument, Book::class);
    $objectManager->persist($book);
    $objectManager->flush(['flushBatchSize' => 10]);

    // Then
    expect($objectManager->find(Book::class, 4))->toBeInstanceOf(Book::class);
})->depends('it retrieves all books');
