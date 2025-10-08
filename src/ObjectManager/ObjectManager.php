<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\ObjectManager;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Manager\ObjectManager as BaseObjectManager;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Misc\NullEventDispatcher;
use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\Criteria\DocumentsCriteriaWrapper;
use Honey\ODM\Meilisearch\Mapper\DocumentMapper;
use Honey\ODM\Meilisearch\Repository\ObjectRepository;
use Honey\ODM\Meilisearch\Repository\ObjectRepositoryInterface;
use Honey\ODM\Meilisearch\Transport\MeiliTransport;
use Meilisearch\Client;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @extends BaseObjectManager<AsDocument, AsAttribute, DocumentsCriteriaWrapper>
 * @phpstan-import-type MeiliTransportOptions from MeiliTransport
 */
final class ObjectManager extends BaseObjectManager
{
    /**
     * @var array<class-string, ObjectRepositoryInterface<object>>
     */
    protected array $repositories = []; // @phpstan-ignore property.phpDocType

    /**
     * @param MeiliTransportOptions $options
     * @param ClassMetadataRegistry $classMetadataRegistry
     */
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

    /**
     * @template O of object
     * @param class-string<O> $className
     * @return ObjectRepositoryInterface<O>
     */
    public function getRepository(string $className): ObjectRepositoryInterface // @phpstan-ignore method.childReturnType
    {
        return $this->repositories[$className] // @phpstan-ignore return.type
            ?? $this->registerRepository($className, new ObjectRepository($this, $className)); // @phpstan-ignore argument.type
    }
}
