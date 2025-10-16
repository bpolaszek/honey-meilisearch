# 🐝 Honey / Meilisearch

A powerful Object Document Mapper (ODM) for [Meilisearch](https://www.meilisearch.com/), inspired by Doctrine ORM.

[![CI Workflow](https://github.com/bpolaszek/honey-meilisearch/actions/workflows/ci-workflow.yml/badge.svg)](https://github.com/bpolaszek/honey-meilisearch/actions/workflows/ci-workflow.yml)
[![codecov](https://codecov.io/gh/bpolaszek/honey-meilisearch/branch/main/graph/badge.svg)](https://codecov.io/gh/bpolaszek/honey-meilisearch)

## Features

- 🚀 **Modern PHP**: Requires PHP 8.4+ with full type safety
- 🏷️ **Attribute-based Configuration**: Use PHP 8 attributes to configure your entities
- 🔍 **Flexible Querying**: Support for multiple query types (arrays, query builders, Meilisearch queries)
- 🔄 **Property Transformers**: Built-in transformers for dates, relations, and custom data types
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

Use PHP attributes to configure your entities:

```php
<?php

use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Config\AsAttribute;

#[AsDocument('books')] // <-- Mark class as a Meilisearch document
final class Book // <-- Yes, classes can be final ! 🤩
{
    public function __construct(
        #[AsAttribute(primary: true)] // <-- Exactly 1 property must be marked as primary key
        public int $id,
        
        #[AsAttribute(name: 'title')] // <-- Optionally set the attribute name on Meilisearch's side
        public string $name,
        
        #[AsAttribute]
        public ?string $cover = null,
        
        #[AsAttribute(name: 'isbn13')]
        public ?string $isbn = null,
    ) {}
}
```

### 2. Configure the Object Manager

```php
<?php

use Honey\ODM\Meilisearch\ObjectManager\ObjectManager;
use Meilisearch\Client;

// Create Meilisearch client
$client = new Client('http://localhost:7700', 'your-master-key');

// Create Object Manager
$objectManager = new ObjectManager($client);

// Get repository for your entity
$bookRepository = $objectManager->getRepository(Book::class); // <-- This will automatically read the `AsDocument` / `AsAttribute` attributes
```

### 3. Basic Operations

```php
<?php

// Find all books
$books = $bookRepository->findAll();

// Find by ID
$book = $bookRepository->find(1);

// Find by criteria (array)
$books = $bookRepository->findBy(['cover' => 'hardcover']);

// Find one by criteria
$book = $bookRepository->findOneBy(['isbn' => '978-0123456789']); // <-- The ODM will know that `isbn` means `isbn13` on Meilisearch's side

// Using query builder for complex queries
$builder = $bookRepository->createCriteriaBuilder();
$books = $bookRepository->findBy(
    $builder->addFilter(
        $builder->field('name')->contains('PHP')
    )->build()
);
```

## Configuration

### Document Configuration

The `#[AsDocument]` attribute marks a class as a Meilisearch document:

```php
#[AsDocument('my_index_name')]
class MyEntity
{
    // ...
}
```

### Attribute Configuration

The `#[AsAttribute]` attribute configures individual properties:

```php
class Author
{
    public function __construct(
        // Primary key with custom field name
        #[AsAttribute(name: 'author_id', primary: true)]
        public int $id,
        
        // Custom field name
        #[AsAttribute(name: 'author_name')]
        public string $name,
        
        // Default field name (uses property name)
        #[AsAttribute]
        public ?string $email = null,
        
        // With data transformer
        #[AsAttribute(
            name: 'created_at', 
            transformer: DateTimeImmutableTransformer::class
        )]
        public ?DateTimeInterface $createdAt = null,
        
        #[AsAttribute(
            name: 'books', 
            transformer: new TransformerMetadata(RelationsTransformer::class, ['target_class' => Book::class]))]
        public array $books = [], // <-- Relations are automatically handled by the ODM
    ) {}
}
```

## Data Transformers

Transform data between PHP objects and Meilisearch documents:

### Built-in Transformers

- `DateTimeImmutableTransformer`: Convert DateTimeImmutable objects to/from strings
- `RelationTransformer`: Handle ManyToOne-like relations ⚠️
- `RelationsTransformer`: Handle OneToMany-like relations ⚠️

⚠️ _Since Meilisearch is not a relational database, it has no foreign key constraints: use this at your own risk!_

### Custom Transformers

```php
use Honey\ODM\Core\Config\TransformerMetadata;

#[AsAttribute(transformer: new TransformerMetadata(
    MyCustomTransformer::class,
    ['option1' => 'value1']
))]
public mixed $myProperty;
```

To make your ObjectManager aware of your custom transformer, you need to register it in a PSR-11 compliant container 
(along with the built-in transformers if you use them), and inject it to the document mapper:

```php
use Honey\ODM\Core\Mapper\PropertyTransformer\BuiltinTransformers;
use Honey\ODM\Meilisearch\Mapper\DocumentMapper;
use Honey\ODM\Meilisearch\ObjectManager\ObjectManager;

$container->set(MyCustomTransformer::class, new MyCustomTransformer());
foreach (new BuiltinTransformers() as $className => $transformer) {
    $container->set($className, $transformer);
}
$objectManager = new ObjectManager(documentMapper: new DocumentMapper(transformers: $container));
```

## Query Builder

Build complex queries using the fluent query builder:

```php
$builder = $repository->createCriteriaBuilder();

$results = $repository->findBy(
    $builder
        ->addFilter($builder->field('category')->equals('fiction'))
        ->addFilter($builder->field('year')->greaterThan(2020))
        ->build()
);
```

## Advanced Usage

### Custom Repository

Extend the base repository for domain-specific methods:

```php
use Honey\ODM\Meilisearch\Repository\ObjectRepository;

class BookRepository extends ObjectRepository
{
    public function findByAuthor(string $authorName): iterable
    {
        return $this->findBy(['author_name' => $authorName]);
    }
    
    public function findRecentBooks(): iterable
    {
        $builder = $this->createCriteriaBuilder();
        return $this->findBy(
            $builder->addFilter(
                $builder->field('created_at')->greaterThan(
                    (new DateTimeImmutable('-1 month'))->format('c')
                )
            )->build()
        );
    }
}
```

Once instantiated, register the repository with the Object Manager as  early as possible in your application:

```php
$objectManager->registerRepository(Book::class, $bookRepository);
```

### Persist your data

Honey ODM is heavily inspired by Doctrine ORM. You can persist your data using the Object Manager:

```php
$book = new Book(1, 'PHP: The Right Way');

$bookRepository->persist($book);
$objectManager->flush();

$book->isbn = '9780123456789';
$objectManager->flush(); // <-- Change on $book detected, Meilisearch updated

$bookRepository->remove($book);
$objectManager->flush(); // <-- $book removed from Meilisearch
```

### Events

Bring your own (PSR-14 compliant) event dispatcher, and hook your logic to lifecycle events:

```php
$eventDispatcher = new EventDispatcher();
$objectManager = new ObjectManager($client, eventDispatcher: $eventDispatcher);
$eventDispatcher->addListener(PrePersistEvent::class, function (PrePersistEvent $event) {
    var_dump($event->object); // <-- The object being persisted
})

// ...
```

## Testing

This package includes comprehensive test coverage using Pest PHP:

```bash
# Run tests
composer test:run

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
5. Run tests: `composer test:run`

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

