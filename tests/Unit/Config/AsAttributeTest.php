<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Config;

use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Author;

it('collects Meilisearch platform metadata alongside core attributes', function () {
    $metadata = new ClassMetadataRegistry()->getClassMetadata(Author::class);

    expect($metadata->collection)->toBe('authors')
        ->and($metadata->propertiesMetadata['name']->getPlatformMetadata(AsAttribute::class)?->filterable)->toBeTrue()
        ->and($metadata->propertiesMetadata['name']->getPlatformMetadata(AsAttribute::class)?->sortable)->toBeNull()
        ->and($metadata->propertiesMetadata['createdAt']->getPlatformMetadata(AsAttribute::class)?->sortable)->toBeTrue()
        ->and($metadata->propertiesMetadata['id']->getPlatformMetadata(AsAttribute::class))->toBeNull()
    ;
});
