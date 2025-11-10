<?php

namespace Honey\ODM\Meilisearch\Tests\Unit\Criteria;

use Honey\ODM\Meilisearch\Criteria\GeoPoint;
use InvalidArgumentException;

describe('GeoPoint', function () {
    it('renders as string', function () {
        $point = new GeoPoint(1.2, 3.4);

        expect((string) $point)->toBe('_geoPoint(1.2,3.4)');
    });

    it('parses a string', function () {
        $string = '_geoPoint(1.2,3.4)';

        $point = GeoPoint::fromString($string);
        expect($point->latitude)->toBe(1.2)
            ->and($point->longitude)->toBe(3.4);
    });

    it('complains when it cannot parse a string', function () {
        GeoPoint::fromString('nope(1.2,3.4)');
    })->throws(InvalidArgumentException::class);
});
