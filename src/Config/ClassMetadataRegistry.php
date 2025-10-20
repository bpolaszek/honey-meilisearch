<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Config;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryTrait;
use IteratorAggregate;
use Traversable;

/**
 * @implements ClassMetadataRegistryInterface<AsDocument<object, AsAttribute>, AsAttribute>
 * @implements IteratorAggregate<class-string, AsDocument<object, AsAttribute>>
 */
final class ClassMetadataRegistry implements ClassMetadataRegistryInterface, IteratorAggregate
{
    /**
     * @use ClassMetadataRegistryTrait<AsDocument, AsAttribute>
     */
    use ClassMetadataRegistryTrait;

    public function getIdFromObject(object $object): mixed
    {
        $classMetadata = $this->getClassMetadata($object::class);

        $idPropertyMetadata = $classMetadata->getIdPropertyMetadata();
        $propertyName = $idPropertyMetadata->reflection->name;

        return $this->propertyAccessor->getValue($object, $propertyName);
    }

    /**
     * @param array<string, mixed> $document
     * @param class-string<object> $className
     */
    public function getIdFromDocument(array $document, string $className): mixed
    {
        $classMetadata = $this->getClassMetadata($className);

        $idPropertyMetadata = $classMetadata->getIdPropertyMetadata();
        $propertyName = $idPropertyMetadata->name ?? $idPropertyMetadata->reflection->name;

        return $this->propertyAccessor->getValue((object) $document, $propertyName);
    }

    public function getIterator(): Traversable
    {
        yield from $this->storage; // @phpstan-ignore generator.valueType
    }
}
