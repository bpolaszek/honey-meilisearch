<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Book;
use LogicException;
use stdClass;
use WeakMap;

use function Honey\ODM\Meilisearch\index_uid;
use function Honey\ODM\Meilisearch\iterable_chunk;
use function Honey\ODM\Meilisearch\weakmap_values;

describe('index_uid()', function () {
    it('returns the collection name as index uid', function () {
        $classMetadata = new ClassMetadataRegistry()->getClassMetadata(Book::class);

        expect(index_uid($classMetadata))->toBe('books');
    });

    it('complains when the class has no collection name', function () {
        $document = new #[AsDocument] class {
            #[AsField(primary: true)]
            public int $id = 1;
        };
        $classMetadata = new ClassMetadataRegistry()->getClassMetadata($document::class);

        index_uid($classMetadata);
    })->throws(LogicException::class);
});

describe('weakmap_values()', function () {

    it('returns all values of a weakmap', function () {
        $foo = new stdClass();
        $bar = new stdClass();
        $weakmap = new WeakMap();
        $weakmap[$foo] = 'foo';
        $weakmap[$bar] = 'bar';

        $values = weakmap_values($weakmap);
        expect($values)->toBe([
            'foo',
            'bar',
        ]);
    });
});


describe('iterable_chunks()', function () {
    it('chunks arrays', function () {
        $data = [
            'foo',
            'bar',
            'baz',
        ];

        expect(iterable_chunk($data, 2))->toBe([
            ['foo', 'bar'],
            ['baz'],
        ]);
    });
    it('creates just 1 big chunk by default', function () {
        $data = [
            'foo',
            'bar',
            'baz',
        ];

        expect(iterable_chunk($data))->toBe([
            ['foo', 'bar', 'baz'],
        ]);
    });
});
