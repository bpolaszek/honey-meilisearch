<?php

namespace Honey\ODM\Meilisearch\Tests\Integration;

use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Schema\SchemaUpdater;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Author;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Book;
use Meilisearch\Exceptions\ApiException;

use function Honey\ODM\Meilisearch\Tests\meili;

describe('Schema Updater', function () {

    $updater = new SchemaUpdater(
        meili(),
        new ClassMetadataRegistry(configurations: [
            Author::class => new AsDocument('authors'),
            Book::class => new AsDocument('books'),
        ]),
    );

    it('updates schema', function () use ($updater) {
        $updater->updateSchema();

        $filterableAttributes = meili()->index('authors')->getFilterableAttributes();
        expect($filterableAttributes)->toBe(['author_id', 'author_name']);

        $sortableAttributes = meili()->index('authors')->getSortableAttributes();
        expect($sortableAttributes)->toBe(['author_id', 'created_at']);
    });

    it('drops schema', function () use ($updater) {
        $updater->dropSchema();

        expect(fn () => meili()->index('authors')->stats())->toThrow(ApiException::class)
            ->and(fn () => meili()->index('books')->stats())->toThrow(ApiException::class);
    })->depends('it updates schema');
});
