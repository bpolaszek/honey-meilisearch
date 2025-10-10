<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Config;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Config\TransformerMetadataInterface;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Tests\Implementation\Document\Author;

it('returns metadata', function () {
    $registry = new ClassMetadataRegistry();
    $metadata = $registry->getClassMetadata(Author::class);
    expect($metadata)->toBeInstanceOf(AsDocument::class)
        ->and($metadata->index)->toBe('authors')
        ->and($metadata->className)->toBe(Author::class)
        ->and($metadata->reflection)->toBe(Reflection::class(Author::class))
        ->and($metadata->propertiesMetadata)->toHaveKeys(['id', 'name', 'books', 'createdAt'])
        ->and($metadata->propertiesMetadata['id'])->toBeInstanceOf(AsAttribute::class)
        ->and($metadata->propertiesMetadata['name'])->toBeInstanceOf(AsAttribute::class)
        ->and($metadata->propertiesMetadata['books'])->toBeInstanceOf(AsAttribute::class)
        ->and($metadata->propertiesMetadata['createdAt'])->toBeInstanceOf(AsAttribute::class)
        ->and($metadata->propertiesMetadata['id']->getTransformer())->toBeNull()
        ->and($metadata->propertiesMetadata['name']->getTransformer())->toBeNull()
        ->and($metadata->propertiesMetadata['books']->getTransformer())->toBeInstanceOf(TransformerMetadataInterface::class)
        ->and($metadata->propertiesMetadata['createdAt']->getTransformer())->toBeInstanceOf(TransformerMetadataInterface::class)
        ->and($metadata->getIdPropertyMetadata()->reflection->name)->toBe('id')
        ;
});



it('returns the id of an object', function () {
    $registry = new ClassMetadataRegistry();
    $object = new Author(1984, 'George Orwell');
    expect($registry->getIdFromObject($object))->toBe(1984);
});

it('returns the id of a document', function () {
    $registry = new ClassMetadataRegistry();
    $document = ['author_id' => 1984, 'author_name' => 'George Orwell'];
    expect($registry->getIdFromDocument($document, Author::class))->toBe(1984);
});
