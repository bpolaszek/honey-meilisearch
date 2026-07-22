# 🐝 Honey / Meilisearch

A powerful Object Document Mapper (ODM) for [Meilisearch](https://www.meilisearch.com/), built on top of [Honey ODM Core](https://github.com/bpolaszek/honey-odm).

[![CI Workflow](https://github.com/bpolaszek/honey-meilisearch/actions/workflows/ci-workflow.yml/badge.svg)](https://github.com/bpolaszek/honey-meilisearch/actions/workflows/ci-workflow.yml)
[![codecov](https://codecov.io/gh/bpolaszek/honey-meilisearch/branch/main/graph/badge.svg)](https://codecov.io/gh/bpolaszek/honey-meilisearch)

## Features

- 🚀 **Modern PHP**: Requires PHP 8.4+ with full type safety
- 🏷️ **Attribute-based Configuration**: Use PHP 8 attributes to configure your entities
- 🔍 **Flexible Querying**: Support for multiple query types (arrays, the portable `Criteria` API, native Meilisearch queries)
- 🔄 **Property Transformers**: Built-in transformers for dates, enums, relations, and custom data types
- 📦 **Repository Pattern**: Clean data access layer with repository interfaces
- 🧪 **100% Test Coverage**: Thoroughly tested with Pest PHP 💯
- ⚡  **Batch Processing**: Efficient bulk operations with chunking support
- 🧲 **Event system**: Pre/Post Persist/Update/Remove events

## Installation

Install via Composer:

```bash
composer require honey-odm/meilisearch
```

## Quick Start

### 1. Define Your Entities

Core attributes (`AsDocument`, `AsField`) describe the mapping and are shared across every Honey ODM
implementation. Meilisearch-specific concerns (filterable/sortable attributes) live in the `AsAttribute`
attribute from this package:

```php
<?php

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Meilisearch\Config\AsAttribute;

#[AsDocument(collection: 'books')] // <-- Mark class as a Meilisearch document
final class Book // <-- Yes, classes can be final ! 🤩
{
    public function __construct(
        #[AsField(primary: true)] // <-- Exactly 1 property must be marked as primary key
        public int $id,

        #[AsField(name: 'title')] // <-- Optionally set the field name on Meilisearch's side
        public string $name,

        #[AsField]
        #[AsAttribute(filterable: true)] // <-- Meilisearch-specific: make this field filterable
        public ?string $cover = null,

        #[AsField(name: 'isbn13')]
        public ?string $isbn = null,
    ) {}
}
```

The same annotated class works on any other Honey ODM implementation — the `#[AsAttribute]` flags are
simply ignored there.

### 2. Configure the Object Manager

```php
<?php

use Honey\ODM\Meilisearch\ObjectManagerFactory;
use Meilisearch\Client;

// Create Meilisearch client
$client = new Client('http://localhost:7700', 'your-master-key');

// Create Object Manager
$objectManager = ObjectManagerFactory::create($client);

// Get repository for your entity
$bookRepository = $objectManager->getRepository(Book::class); // <-- This will automatically read the `AsDocument` / `AsField` attributes
```

### 3. Basic Operations

```php
<?php

use Honey\ODM\Core\Criteria\Criteria;

use function Honey\ODM\Core\Criteria\field;

// Find all books
$books = $bookRepository->findAll();

// Find by ID
$book = $bookRepository->find(1);

// Find by criteria (array of equality filters, AND-combined)
$books = $bookRepository->findBy(['cover' => 'hardcover']);

// Find one by criteria
$book = $bookRepository->findOneBy(['isbn' => '978-0123456789']); // <-- The ODM will know that `isbn` means `isbn13` on Meilisearch's side

// Using the portable Criteria API for complex queries
$books = $bookRepository->findBy(
    Criteria::create()->where(field('name')->contains('PHP'))
);
```

## Configuration

### Document Configuration

The `#[AsDocument]` attribute marks a class as a Meilisearch document:

```php
#[AsDocument(collection: 'my_index_name')]
class MyEntity
{
    // ...
}
```

### Field Configuration

The `#[AsField]` attribute configures individual properties (storage-side field name, primary key,
transformer). Meilisearch's own concerns — filterable / sortable attributes — are configured
alongside it with `#[AsAttribute]`:

```php
use DateTimeInterface;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Meilisearch\Config\AsAttribute;

#[AsDocument(collection: 'authors')]
class Author
{
    public function __construct(
        // Primary key with custom field name
        #[AsField(name: 'author_id', primary: true)]
        public int $id,

        // Custom field name, filterable on Meilisearch's side
        #[AsField(name: 'author_name')]
        #[AsAttribute(filterable: true)]
        public string $name,

        // With a built-in data transformer, sortable on Meilisearch's side
        #[AsField(name: 'created_at', transformer: DateTimeImmutableTransformer::class)]
        #[AsAttribute(sortable: true)]
        public ?DateTimeInterface $createdAt = null,
    ) {}
}
```

Once your entities are mapped, apply the schema (index creation, filterable/sortable attributes) to
Meilisearch with the `SchemaUpdater`:

```php
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Schema\SchemaUpdater;

$updater = new SchemaUpdater($client, new ClassMetadataRegistry(configurations: [Author::class, Book::class]));
$updater->updateSchema();
```

### Index Prefixes

If you run separate Meilisearch environments (e.g. `local-`, `staging-`), give `ObjectManagerFactory`
an `$indexPrefix` so every index name is prefixed consistently — without changing your `#[AsDocument]`
collection names:

```php
$objectManager = ObjectManagerFactory::create($client, indexPrefix: 'staging-');
```

## Data Transformers

Transform data between PHP objects and Meilisearch documents:

### Built-in Transformers

- `DateTimeImmutableTransformer`: Convert `DateTimeImmutable` objects to/from strings
- `BackedEnumTransformer`: Convert backed enums to/from their scalar value
- `StringableTransformer`: Convert `Stringable` objects to/from strings
- `RelationTransformer`: Handle ManyToOne-like relations ⚠️
- `RelationsTransformer`: Handle OneToMany-like relations ⚠️

⚠️ _Since Meilisearch is not a relational database, it has no foreign key constraints: use this at your own risk!_

### Custom Transformers

```php
use Honey\ODM\Core\Config\TransformerMetadata;

#[AsField(transformer: new TransformerMetadata(
    MyCustomTransformer::class,
    ['option1' => 'value1']
))]
public mixed $myProperty;
```

`PropertyTransformers` already registers every built-in transformer; register your own alongside them
and inject the container into the document mapper:

```php
use Honey\ODM\Core\Mapper\DocumentMapper;
use Honey\ODM\Core\Mapper\PropertyTransformer\PropertyTransformers;
use Honey\ODM\Meilisearch\ObjectManagerFactory;

$transformers = new PropertyTransformers();
$transformers->register(new MyCustomTransformer());

$objectManager = ObjectManagerFactory::create(
    $client,
    documentMapper: new DocumentMapper(transformers: $transformers),
);
```

## Criteria API

Build complex, platform-agnostic queries with `Criteria`:

```php
use Honey\ODM\Core\Criteria\Criteria;

use function Honey\ODM\Core\Criteria\field;
use function Honey\ODM\Core\Criteria\not;

$results = $repository->findBy(
    Criteria::create()
        ->where(
            field('category')->equals('fiction'),
            not(field('year')->lessThan(2020)),
        )
        ->orderBy('year', 'desc')
        ->limit(10)
);
```

The same `Criteria` object is portable across Honey ODM implementations; this package compiles it to
native Meilisearch filters (or a `SearchQuery` when `->search(...)` is used), throwing rather than
silently degrading whenever an expression can't be translated (see `CriteriaCompiler`).

## Advanced Usage

### Custom Repository

`ObjectRepository` (the default repository) is `final` — compose `ObjectRepositoryTrait` into your own
class instead for domain-specific methods:

```php
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Meilisearch\Repository\ObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Repository\ObjectRepositoryTrait;

use function Honey\ODM\Core\Criteria\field;

/**
 * @implements ObjectRepositoryInterface<Book>
 */
final class BookRepository implements ObjectRepositoryInterface
{
    use ObjectRepositoryTrait;

    public function findByAuthor(string $authorName): iterable
    {
        return $this->findBy(['author_name' => $authorName]);
    }

    public function findRecentBooks(): iterable
    {
        return $this->findBy(
            Criteria::create()->where(
                field('created_at')->greaterThan((new DateTimeImmutable('-1 month'))->format('c'))
            )
        );
    }
}
```

Once instantiated, register the repository with the Object Manager as early as possible in your application:

```php
$bookRepository = new BookRepository($objectManager, Book::class);
$objectManager->registerRepository(Book::class, $bookRepository);
```

### Persist your data

The Object Manager (not the repository) owns persistence:

```php
$book = new Book(1, 'PHP: The Right Way');

$objectManager->persist($book);
$objectManager->flush();

$book->isbn = '9780123456789';
$objectManager->flush(); // <-- Change on $book detected, Meilisearch updated

$objectManager->remove($book);
$objectManager->flush(); // <-- $book removed from Meilisearch
```

### Events

Bring your own (PSR-14 compliant) event dispatcher, and hook your logic to lifecycle events:

```php
use Honey\ODM\Core\Event\PrePersistEvent;
use Honey\ODM\Meilisearch\ObjectManagerFactory;

$eventDispatcher = new EventDispatcher(); // <-- Any PSR-14 compliant dispatcher
$objectManager = ObjectManagerFactory::create($client, eventDispatcher: $eventDispatcher);
$eventDispatcher->addListener(PrePersistEvent::class, function (PrePersistEvent $event) {
    var_dump($event->object); // <-- The object being persisted
});

// ...
```

## Testing

This package includes comprehensive test coverage using Pest PHP:

```bash
# Run tests
composer tests:run

# Check types
composer types:check

# Check code style
composer style:check

# Perform all checks at once
composer ci:check
```

## Development

### Requirements

- PHP 8.4+
- Meilisearch server
- Composer

### Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Start Meilisearch server
4. Optionally configure your Meilisearch connection in your `.env.local`
5. Run tests: `composer tests:run`

### Code Quality

This project maintains high code quality standards:

- **100% test coverage** requirement
- **PHPStan level max** static analysis
- **PHP-CS-Fixer** code style enforcement
- **Pest PHP** for testing

## Contributing

We welcome contributions! Please see our contributing guidelines:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes and add tests
4. Ensure all tests pass: `composer ci:check`
5. Commit your changes: `git commit -m 'Add amazing feature'`
6. Push to the branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Contribution Guidelines

- Maintain 100% test coverage
- Follow existing code style (enforced by PHP-CS-Fixer)
- Add PHPDoc comments for public methods
- Update documentation for new features
- Write meaningful commit messages

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Related Projects

- [Honey ODM Core](https://github.com/bpolaszek/honey-odm) - The core ODM framework
- [Meilisearch PHP](https://github.com/meilisearch/meilisearch-php) - Official Meilisearch PHP client
- [Meilisearch](https://github.com/meilisearch/meilisearch) - The Meilisearch search engine
