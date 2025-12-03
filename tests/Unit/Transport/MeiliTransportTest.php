<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Transport;

use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\DocumentResultset;
use Honey\ODM\Meilisearch\Result\SearchResultset;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use Meilisearch\Client;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Contracts\SearchQuery;

it('returns DocumentResultset when query is DocumentsQuery', function () {
    $transport = new MeiliTransport(new Client('http://example.com:7700'));
    $criteria = new DocumentsCriteriaWrapper('my-index', new DocumentsQuery());

    $result = $transport->retrieveDocuments($criteria);

    expect($result)->toBeInstanceOf(DocumentResultset::class);
});

it('returns SearchResultset when query is SearchQuery', function () {
    $transport = new MeiliTransport(new Client('http://example.com:7700'));
    $criteria = new DocumentsCriteriaWrapper('my-index', new SearchQuery());

    $result = $transport->retrieveDocuments($criteria);

    expect($result)->toBeInstanceOf(SearchResultset::class);
});
