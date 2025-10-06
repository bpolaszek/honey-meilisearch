<?php

namespace Honey\ODM\Meilisearch\ObjectManager;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Manager\ObjectManager as BaseObjectManager;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Misc\NullEventDispatcher;
use Honey\ODM\Core\Repository\ObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Mapper\DocumentMapper;
use Honey\ODM\Meilisearch\Repository\ObjectRepository;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use Meilisearch\Client;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ObjectManager extends BaseObjectManager
{
    public function __construct(
        public readonly Client $meili,
        array $options = [],
        ClassMetadataRegistryInterface $classMetadataRegistry = new ClassMetadataRegistry(),
        DocumentMapperInterface $documentMapper = new DocumentMapper(),
        EventDispatcherInterface $eventDispatcher = new NullEventDispatcher(),
    ) {
        parent::__construct(
            $classMetadataRegistry,
            $documentMapper,
            $eventDispatcher,
            new MeiliTransport($this->meili, $options),
        );
    }

    public function getRepository(string $className): ObjectRepositoryInterface
    {
        return $this->repositories[$className]
            ?? $this->registerRepository($className, new ObjectRepository($this, $className));
    }
}
