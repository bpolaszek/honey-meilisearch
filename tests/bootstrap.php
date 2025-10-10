<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests;

use Symfony\Component\Dotenv\Dotenv;

use function dirname;
use function method_exists;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}
