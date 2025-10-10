<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Implementation\Document;

use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;

#[AsDocument('books')]
final class Book
{
    public function __construct(
        #[AsAttribute(primary: true)]
        public int $id,
        #[AsAttribute(name: 'title')]
        public string $name,
        #[AsAttribute(transformer: new TransformerMetadata(RelationTransformer::class))]
        public ?Author $author,
    ) {
    }
}
