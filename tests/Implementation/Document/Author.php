<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Implementation\Document;

use DateTimeInterface;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationsTransformer;
use Honey\ODM\Meilisearch\Config\Attribute;

#[AsDocument(collection: 'authors')]
final class Author
{
    public function __construct(
        #[AsField(name: 'author_id', primary: true)]
        public int $id,
        #[AsField(name: 'author_name')]
        #[Attribute(filterable: true)]
        public string $name,
        #[AsField(name: 'books', transformer: new TransformerMetadata(
            RelationsTransformer::class,
            ['target_class' => Book::class],
        ))]
        public array $books = [],
        #[AsField(name: 'created_at', transformer: DateTimeImmutableTransformer::class)]
        #[Attribute(sortable: true)]
        public ?DateTimeInterface $createdAt = null,
    ) {
    }
}
