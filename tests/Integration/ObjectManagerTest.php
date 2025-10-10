<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Integration;

use Honey\ODM\Meilisearch\ObjectManager\ObjectManager;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Book;

use function afterAll;
use function array_column;
use function beforeAll;
use function dirname;
use function file_get_contents;
use function Honey\ODM\Meilisearch\Tests\meili;

beforeAll(function () {
    $tasks = [];
    $tasks[] = meili()->deleteIndex('authors');
    $tasks[] = meili()->deleteIndex('books');
    $tasks[] = meili()->createIndex('authors', ['primaryKey' => 'author_id']);
    $tasks[] = meili()->createIndex('books');
    meili()->waitForTasks(array_column($tasks, 'taskUid'));
    $tasks[] = meili()->index('authors')->addDocumentsJson(file_get_contents(dirname(__DIR__) . '/fixtures/authors.json'));
    $tasks[] = meili()->index('books')->addDocumentsJson(file_get_contents(dirname(__DIR__) . '/fixtures/books.json'));
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
    $repository = $objectManager->getRepository(Book::class);
    $allBooks = $repository->findAll();
    expect($allBooks)->toBeInstanceOf(ObjectResultset::class)
        ->and($allBooks)->toHaveCount(164)
        ;
});
