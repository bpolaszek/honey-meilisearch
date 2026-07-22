<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch;

use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Mapper\DocumentMapper;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Misc\NullEventDispatcher;
use Honey\ODM\Meilisearch\Repository\ObjectRepository;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use Meilisearch\Client;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Wires up an ObjectManager backed by Meilisearch.
 *
 * @phpstan-import-type MeiliTransportOptions from MeiliTransport
 */
final readonly class ObjectManagerFactory
{
    /**
     * @param MeiliTransportOptions $options
     */
    public static function create(
        Client $meili = new Client('http://localhost:7700'),
        array $options = [],
        ClassMetadataRegistryInterface $classMetadataRegistry = new ClassMetadataRegistry(),
        DocumentMapperInterface $documentMapper = new DocumentMapper(),
        EventDispatcherInterface $eventDispatcher = new NullEventDispatcher(),
        string $indexPrefix = '',
    ): ObjectManager {
        $transport = new MeiliTransport($meili, $options, $indexPrefix);

        return new ObjectManager(
            transport: $transport,
            classMetadataRegistry: $classMetadataRegistry,
            documentMapper: $documentMapper,
            eventDispatcher: $eventDispatcher,
            defaultFlushOptions: $transport->options,
            repositoryFactory: static fn (ObjectManager $objectManager, string $className) => new ObjectRepository(
                $objectManager,
                $className,
            ),
        );
    }
}
