<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use ReflectionClass;
use RuntimeException;

use function array_find;

/**
 * @template O of object
 * @template P of AsAttribute
 *
 * @implements ClassMetadataInterface<O, P>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsDocument implements ClassMetadataInterface
{
    /**
     * @var class-string<O>
     */
    public string $className;

    /**
     * @var ReflectionClass<O>
     */
    public ReflectionClass $reflection;

    /**
     * @var array<string, P>
     */
    public array $propertiesMetadata = [];

    public function __construct(
        public string $index,
    ) {
    }

    public function getIdPropertyMetadata(): PropertyMetadataInterface
    {
        return array_find(
            $this->propertiesMetadata,
            fn (PropertyMetadataInterface $metadata) => $metadata->primary,
        ) ?? throw new RuntimeException('No primary property found in class metadata');
    }

    public function getAttributeName(string $propertyName): string
    {
        return $this->propertiesMetadata[$propertyName]?->name // @phpstan-ignore nullsafe.neverNull
            ?? $this->propertiesMetadata[$propertyName]?->reflection?->name // @phpstan-ignore nullsafe.neverNull
            ?? throw new RuntimeException("No attribute mapped to `$propertyName` was found.");
    }
}
