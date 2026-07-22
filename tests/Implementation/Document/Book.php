<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Implementation\Document;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use Honey\ODM\Meilisearch\Config\AsAttribute;

#[AsDocument(collection: 'books')]
final class Book
{
    public function __construct(
        #[AsField(primary: true)]
        public int $id,
        #[AsField(name: 'title')]
        public string $name,
        #[AsField(transformer: new TransformerMetadata(RelationTransformer::class))]
        #[AsAttribute(filterable: true)]
        public ?Author $author,
        #[AsField]
        public ?string $cover = null,
        #[AsField]
        #[AsAttribute(filterable: true)]
        public ?string $language = null,
        #[AsField]
        public array $details = [],
        #[AsField(name: 'isbn13')]
        #[AsAttribute(filterable: true)]
        public ?string $isbn = null,
    ) {
    }
}
