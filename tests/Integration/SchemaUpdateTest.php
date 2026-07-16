<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Integration;

use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Schema\SchemaUpdater;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Author;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Book;
use Meilisearch\Exceptions\ApiException;

use function Honey\ODM\Meilisearch\Tests\meili;

describe('Schema Updater', function () {
    $updater = new SchemaUpdater(
        meili(),
        new ClassMetadataRegistry(configurations: [Author::class, Book::class]),
    );

    it('updates schema', function () use ($updater) {
        $updater->updateSchema();

        expect(meili()->index('authors')->getFilterableAttributes())->toBe(['author_id', 'author_name'])
            ->and(meili()->index('authors')->getSortableAttributes())->toBe(['author_id', 'created_at'])
            ->and(meili()->index('books')->getFilterableAttributes())->toEqualCanonicalizing(['id', 'author', 'language', 'isbn13'])
            ->and(meili()->index('books')->getSortableAttributes())->toBe(['id'])
        ;
    });

    it('drops schema', function () use ($updater) {
        $updater->dropSchema();

        expect(fn () => meili()->index('authors')->stats())->toThrow(ApiException::class)
            ->and(fn () => meili()->index('books')->stats())->toThrow(ApiException::class);
    })->depends('it updates schema');
});
