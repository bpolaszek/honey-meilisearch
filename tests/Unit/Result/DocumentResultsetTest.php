<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Result;

use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\DocumentResultset;
use Meilisearch\Client;

it('is read-only', function () {
    $resultset = new DocumentResultset(new Client('http://example.com:7700'), new DocumentsCriteriaWrapper('foos'));

    expect(fn () => $resultset[0] = 'foo')->toThrow(\RuntimeException::class);
    expect(function () use ($resultset) {
        unset($resultset[0]);
    })->toThrow(\RuntimeException::class);
});
