<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Result;

use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Result\SearchResultset;
use Meilisearch\Client;
use Meilisearch\Contracts\Http;
use Meilisearch\Contracts\SearchQuery;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Search\SearchResult;
use Psr\Http\Message\StreamInterface;

use const PHP_INT_MAX;

it('is read-only', function () {
    $resultset = new SearchResultset(new Client('http://example.com:7700'), new DocumentsCriteriaWrapper('foos'));

    expect(fn () => $resultset[0] = 'foo')->toThrow(\RuntimeException::class);
    expect(function () use ($resultset) {
        unset($resultset[0]);
    })->toThrow(\RuntimeException::class);
});

it('iterates hits across batches and respects overall limit', function () {
    // Prepare 5 fake documents
    $docs = [];
    for ($i = 1; $i <= 5; $i++) {
        $docs[] = ['id' => $i, 'name' => 'Doc ' . $i];
    }

    $batchSize = 2; // simulate small batch pages

    // Dummy HTTP implementation to satisfy Indexes ctor
    $dummyHttp = new class implements Http {
        public function get(string $path, array $query = [])
        {
        }
        public function post(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function put(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function patch(string $path, $body = null, array $query = [])
        {
        }
        public function delete(string $path, array $query = [])
        {
        }
        public function postStream(string $path, $body = null, array $query = []): StreamInterface
        {
            throw new \RuntimeException('not used');
        }
    };

    // Fake client that returns an Indexes stub with paginated search results
    $client = new class ('http://example.com:7700') extends Client {
        public Http $dummyHttp;
        public array $docs;
        public int $batchSize;
        public function with(Http $http, array $docs, int $batchSize): static
        {
            $this->dummyHttp = $http;
            $this->docs = $docs;
            $this->batchSize = $batchSize;
            return $this;
        }
        public function index(string $uid): Indexes
        {
            $http = $this->dummyHttp;
            $docs = $this->docs;
            $batch = $this->batchSize;

            return new class ($http, $uid, $docs, $batch) extends Indexes {
                public array $docs;
                public int $batchSize;
                public function __construct(Http $http, string $uid, array $docs, int $batchSize)
                {
                    parent::__construct($http, $uid);
                    $this->docs = $docs;
                    $this->batchSize = $batchSize;
                }
                public function search(?string $query, array $searchParams = [], array $options = [])
                {
                    $offset = $searchParams['offset'] ?? 0;
                    // Always return a page equal to batchSize to match SearchResultset pagination logic
                    $hits = array_slice($this->docs, $offset, $this->batchSize);
                    $body = [
                        'offset' => $offset,
                        'limit' => $searchParams['limit'] ?? $this->batchSize,
                        'estimatedTotalHits' => count($this->docs),
                        'processingTimeMs' => 0,
                        'query' => (string) ($query ?? ''),
                        'hits' => $hits,
                    ];

                    return new SearchResult($body);
                }
            };
        }
    };

    $client = $client->with($dummyHttp, $docs, $batchSize);

    $criteria = new DocumentsCriteriaWrapper('foos', null, $batchSize);
    $resultset = new SearchResultset($client, $criteria);

    // Limit overall to 4 using a SearchQuery in the wrapper so SearchResultset->limit = 4
    $refCriteriaQuery = new SearchQuery();
    $refCriteriaQuery->setLimit(4);
    // Recreate criteria with query to set the limit
    $criteria = new DocumentsCriteriaWrapper('foos', $refCriteriaQuery, $batchSize);
    $resultset = new SearchResultset($client, $criteria);

    $items = iterator_to_array($resultset);

    expect($items)->toHaveCount(4)
        ->and($items[0]['id'])->toBe(1)
        ->and($items[3]['id'])->toBe(4)
        ->and($resultset->totalItems)->toBe(5);
});

it('returns empty iterator when no hits', function () {
    $dummyHttp = new class implements Http {
        public function get(string $path, array $query = [])
        {
        }
        public function post(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function put(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function patch(string $path, $body = null, array $query = [])
        {
        }
        public function delete(string $path, array $query = [])
        {
        }
        public function postStream(string $path, $body = null, array $query = []): StreamInterface
        {
            throw new \RuntimeException('not used');
        }
    };

    $client = new class ('http://example.com:7700') extends Client {
        public Http $dummyHttp;
        public function with(Http $http): static
        {
            $this->dummyHttp = $http;
            return $this;
        }
        public function index(string $uid): Indexes
        {
            $http = $this->dummyHttp;

            return new class ($http, $uid) extends Indexes {
                public function search(?string $query, array $searchParams = [], array $options = [])
                {
                    $body = [
                        'offset' => $searchParams['offset'] ?? 0,
                        'limit' => $searchParams['limit'] ?? 20,
                        'estimatedTotalHits' => 0,
                        'processingTimeMs' => 0,
                        'query' => (string) ($query ?? ''),
                        'hits' => [],
                    ];

                    return new SearchResult($body);
                }
            };
        }
    };

    $client = $client->with($dummyHttp);

    $criteria = new DocumentsCriteriaWrapper('foos', null, 10);
    $resultset = new SearchResultset($client, $criteria);

    $items = iterator_to_array($resultset);

    expect($items)->toHaveCount(0)
        ->and(isset($resultset->totalItems))->toBeTrue()
        ->and($resultset->totalItems)->toBe(0);
});

it('count returns PHP_INT_MAX when estimatedTotalHits is null (numbered pagination)', function () {
    $dummyHttp = new class implements Http {
        public function get(string $path, array $query = [])
        {
        }
        public function post(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function put(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function patch(string $path, $body = null, array $query = [])
        {
        }
        public function delete(string $path, array $query = [])
        {
        }
        public function postStream(string $path, $body = null, array $query = []): StreamInterface
        {
            throw new \RuntimeException('not used');
        }
    };

    $client = new class ('http://example.com:7700') extends Client {
        public Http $dummyHttp;
        public function with(Http $http): static
        {
            $this->dummyHttp = $http;
            return $this;
        }
        public function index(string $uid): Indexes
        {
            $http = $this->dummyHttp;

            return new class ($http, $uid) extends Indexes {
                public function search(?string $query, array $searchParams = [], array $options = [])
                {
                    // Return numbered pagination (no estimatedTotalHits)
                    $body = [
                        'hitsPerPage' => 20,
                        'page' => 1,
                        'totalPages' => 1,
                        'totalHits' => 0,
                        'processingTimeMs' => 0,
                        'query' => (string) ($query ?? ''),
                        'hits' => [],
                    ];

                    return new SearchResult($body);
                }
            };
        }
    };

    $client = $client->with($dummyHttp);

    $criteria = new DocumentsCriteriaWrapper('foos');
    $resultset = new SearchResultset($client, $criteria);

    expect(count($resultset))->toBe(PHP_INT_MAX);
});

it('iterates all items until offset exceeds totalItems (covers end-of-iteration branch)', function () {
    // Build a small dataset split across batches
    $docs = [];
    for ($i = 1; $i <= 5; $i++) {
        $docs[] = ['id' => $i, 'name' => 'Doc ' . $i];
    }

    $batchSize = 2;

    $dummyHttp = new class implements Http {
        public function get(string $path, array $query = [])
        {
        }
        public function post(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function put(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function patch(string $path, $body = null, array $query = [])
        {
        }
        public function delete(string $path, array $query = [])
        {
        }
        public function postStream(string $path, $body = null, array $query = []): StreamInterface
        {
            throw new \RuntimeException('not used');
        }
    };

    $client = new class ('http://example.com:7700') extends Client {
        public Http $dummyHttp;
        public array $docs;
        public int $batchSize;
        public function with(Http $http, array $docs, int $batchSize): static
        {
            $this->dummyHttp = $http;
            $this->docs = $docs;
            $this->batchSize = $batchSize;
            return $this;
        }
        public function index(string $uid): Indexes
        {
            $http = $this->dummyHttp;
            $docs = $this->docs;
            $batch = $this->batchSize;
            return new class ($http, $uid, $docs, $batch) extends Indexes {
                public function __construct(Http $http, string $uid, private array $docs, private int $batchSize)
                {
                    parent::__construct($http, $uid);
                }
                public function search(?string $query, array $searchParams = [], array $options = [])
                {
                    $offset = $searchParams['offset'] ?? 0;
                    $hits = array_slice($this->docs, $offset, $this->batchSize);
                    return new SearchResult([
                        'offset' => $offset,
                        'limit' => $searchParams['limit'] ?? $this->batchSize,
                        'estimatedTotalHits' => count($this->docs),
                        'processingTimeMs' => 0,
                        'query' => (string) ($query ?? ''),
                        'hits' => $hits,
                    ]);
                }
            };
        }
    };

    $client = $client->with($dummyHttp, $docs, $batchSize);

    // No explicit limit in query so iteration should naturally stop when offset > totalItems
    $criteria = new DocumentsCriteriaWrapper('foos', null, $batchSize);
    $resultset = new SearchResultset($client, $criteria);

    $items = iterator_to_array($resultset);

    expect($items)->toHaveCount(5)
        ->and($items[0]['id'])->toBe(1)
        ->and($items[4]['id'])->toBe(5)
        ->and($resultset->totalItems)->toBe(5);
});

it('supports ArrayAccess for offsetExists and offsetGet with bounds and types', function () {
    $docs = [
        ['id' => 1], ['id' => 2], ['id' => 3],
    ];
    $batchSize = 2;

    $dummyHttp = new class implements Http {
        public function get(string $path, array $query = [])
        {
        }
        public function post(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function put(string $path, $body = null, array $query = [], ?string $contentType = null)
        {
        }
        public function patch(string $path, $body = null, array $query = [])
        {
        }
        public function delete(string $path, array $query = [])
        {
        }
        public function postStream(string $path, $body = null, array $query = []): StreamInterface
        {
            throw new \RuntimeException('not used');
        }
    };

    $client = new class ('http://example.com:7700') extends Client {
        public Http $dummyHttp;
        public array $docs;
        public int $batchSize;
        public function with(Http $http, array $docs, int $batchSize): static
        {
            $this->dummyHttp = $http;
            $this->docs = $docs;
            $this->batchSize = $batchSize;
            return $this;
        }
        public function index(string $uid): Indexes
        {
            $http = $this->dummyHttp;
            $docs = $this->docs;
            $batch = $this->batchSize;
            return new class ($http, $uid, $docs, $batch) extends Indexes {
                public function __construct(Http $http, string $uid, private array $docs, private int $batchSize)
                {
                    parent::__construct($http, $uid);
                }
                public function search(?string $query, array $searchParams = [], array $options = [])
                {
                    $offset = $searchParams['offset'] ?? 0;
                    $hits = array_slice($this->docs, $offset, $this->batchSize);
                    return new SearchResult([
                        'offset' => $offset,
                        'limit' => $searchParams['limit'] ?? $this->batchSize,
                        'estimatedTotalHits' => count($this->docs),
                        'processingTimeMs' => 0,
                        'query' => (string) ($query ?? ''),
                        'hits' => $hits,
                    ]);
                }
            };
        }
    };

    $client = $client->with($dummyHttp, $docs, $batchSize);
    $criteria = new DocumentsCriteriaWrapper('foos', null, $batchSize);
    $resultset = new SearchResultset($client, $criteria);

    // offsetExists with integers
    expect(isset($resultset[0]))->toBeTrue()
        ->and(isset($resultset[2]))->toBeTrue()
        ->and(isset($resultset[3]))->toBeFalse();

    // offsetExists with non-integer should be false
    expect(isset($resultset['foo']))->toBeFalse();

    // offsetGet with non-integer should throw
    expect(fn () => $resultset['0'])->toThrow(\InvalidArgumentException::class);

    // offsetGet with valid index returns the document
    expect($resultset[1]['id'])->toBe(2);

    // offsetGet out of range returns null
    expect($resultset[42])->toBeNull();
});
