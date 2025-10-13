<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Integration;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Manager\Identities;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\ObjectManager\ObjectManager;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Book;
use InvalidArgumentException;
use Meilisearch\Contracts\DocumentsQuery;
use RuntimeException;
use stdClass;

use function afterAll;
use function array_column;
use function beforeAll;
use function dirname;
use function file_get_contents;
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
    $objectManager = new ObjectManager(meili());
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

it('uses filters', function () {
    $objectManager = new ObjectManager(meili());
    $repository = $objectManager->getRepository(Book::class);

    $query = new DocumentsQuery();
    $books = $repository->findBy($query->setFilter(["language = spa"]));
    expect(count($books))->toBe(3);
});

it('finds a specific book by its id', function () {
    $objectManager = new ObjectManager(meili());
    $repository = $objectManager->getRepository(Book::class);
    /** @var Book $book */
    $book = $repository->find(619);
    expect($book)->toBeInstanceOf(Book::class)
        ->and($book->id)->toBe(619)
        ->and($book->isbn)->toBe('9780439786190')
    ;
});

it('returns null when the document does not exist', function () {
    $objectManager = new ObjectManager(meili());
    $repository = $objectManager->getRepository(Book::class);
    /** @var Book $book */
    $book = $repository->find(1337);
    expect($book)->toBeNull();
});

it('finds a specific book using filters', function () {
    $objectManager = new ObjectManager(meili());
    $repository = $objectManager->getRepository(Book::class);
    $query = new DocumentsQuery()->setFilter(['isbn13 = 9780439786184']);
    /** @var Book $book */
    $book = $repository->findOneBy($query);
    expect($book)->toBeInstanceOf(Book::class)
        ->and($book->isbn)->toBe('9780439786184')
    ;
});

it('complains when criteria are not of the expected type', function () {
    $objectManager = new ObjectManager(meili());
    $repository = $objectManager->getRepository(Book::class);
    $repository->findOneBy(new stdClass()); // @phpstan-ignore argument.type
})->throws(InvalidArgumentException::class);

it('persists stuff', function () {
    $objectManager = new ObjectManager(meili());
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(Book::class);
    $book = $objectManager->find(Book::class, 4);
    assert($book instanceof Book);

    $rememberedStates = Reflection::property(Identities::class, 'rememberedStates')->getValue($objectManager->identities);
    $initialDocument = $rememberedStates[$book];
    assert(is_array($initialDocument));

    // When
    $objectManager->remove($book);
    $objectManager->flush();

    // Then
    expect($objectManager->find(Book::class, 4))->toBeNull();

    // When
    $book = $objectManager->factory($initialDocument, $classMetadata);
    $objectManager->persist($book);
    $objectManager->flush();

    // Then
    expect($objectManager->find(Book::class, 4))->toBeInstanceOf(Book::class);
})->depends('it retrieves all books');
