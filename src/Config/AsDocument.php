<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadata;
use RuntimeException;

/**
 * @template O of object
 * @template P of AsAttribute
 *
 * @extends ClassMetadata<O, P>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsDocument extends ClassMetadata
{
    public function __construct(
        public readonly string $index,
    ) {
    }

    public function getAttributeName(string $propertyName): string
    {
        return $this->propertiesMetadata[$propertyName]?->name // @phpstan-ignore nullsafe.neverNull
            ?? $this->propertiesMetadata[$propertyName]?->reflection?->name // @phpstan-ignore nullsafe.neverNull
            ?? throw new RuntimeException("No attribute mapped to `$propertyName` was found.");
    }
}
