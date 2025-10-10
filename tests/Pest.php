<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests;

use Meilisearch\Client;
use RuntimeException;

function meili(): Client
{
    static $meili;
    $meili ??= new Client(
        $_SERVER['MEILISEARCH_BASE_URI'] ?? throw new RuntimeException("MEILISEARCH_BASE_URI env var missing."),
        $_SERVER['MEILISEARCH_API_KEY'] ?? throw new RuntimeException("MEILISEARCH_API_KEY env var missing."),
    );

    return $meili;
}
