<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Config;

use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Config\Attribute;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Author;

it('collects Meilisearch platform metadata alongside core attributes', function () {
    $metadata = new ClassMetadataRegistry()->getClassMetadata(Author::class);

    expect($metadata->collection)->toBe('authors')
        ->and($metadata->propertiesMetadata['name']->getPlatformMetadata(Attribute::class)?->filterable)->toBeTrue()
        ->and($metadata->propertiesMetadata['name']->getPlatformMetadata(Attribute::class)?->sortable)->toBeNull()
        ->and($metadata->propertiesMetadata['createdAt']->getPlatformMetadata(Attribute::class)?->sortable)->toBeTrue()
        ->and($metadata->propertiesMetadata['id']->getPlatformMetadata(Attribute::class))->toBeNull()
    ;
});
