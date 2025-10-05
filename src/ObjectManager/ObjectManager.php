<?php

namespace Honey\ODM\Meilisearch\ObjectManager;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Manager\ObjectManager as BaseObjectManager;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Misc\NullEventDispatcher;
use Honey\ODM\Meilisearch\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Mapper\DocumentMapper;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use Meilisearch\Client;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ObjectManager extends BaseObjectManager
{
    public function __construct(
        public readonly Client $meili,
        array $options = [],
        public readonly ClassMetadataRegistryInterface $classMetadataRegistry = new ClassMetadataRegistry(),
        public readonly DocumentMapperInterface $documentMapper = new DocumentMapper(),
        public readonly EventDispatcherInterface $eventDispatcher = new NullEventDispatcher(),
    ) {
        parent::__construct(
            $this->classMetadataRegistry,
            $this->documentMapper,
            $this->eventDispatcher,
            new MeiliTransport($this->meili, $options),
        );
    }
}
