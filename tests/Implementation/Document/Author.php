<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Implementation\Document;

use DateTimeInterface;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationsTransformer;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;

#[AsDocument('authors')]
final class Author
{
    public function __construct(
        #[AsAttribute(name: 'author_id', primary: true)]
        public int $id,
        #[AsAttribute(name: 'author_name', filterable: true)]
        public string $name,
        #[AsAttribute(name: 'books', transformer: new TransformerMetadata(
            RelationsTransformer::class,
            ['target_class' => Book::class],
        ))]
        public array $books = [],
        #[AsAttribute(name: 'created_at', transformer: DateTimeImmutableTransformer::class, sortable: true)]
        public ?DateTimeInterface $createdAt = null,
    ) {
    }
}
