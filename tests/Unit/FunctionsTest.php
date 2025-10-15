<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit;

use stdClass;
use WeakMap;

use function Honey\ODM\Meilisearch\iterable_chunk;
use function Honey\ODM\Meilisearch\weakmap_values;

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
