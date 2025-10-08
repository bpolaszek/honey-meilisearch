<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Config\TransformerMetadataInterface;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class AsAttribute implements PropertyMetadataInterface
{
    public ReflectionProperty $reflection;

    /**
     * @var AsDocument<object, AsAttribute>
     */
    public ClassMetadataInterface $classMetadata;

    public function __construct(
        public readonly ?string $name = null,
        public readonly bool $primary = false,
        private readonly TransformerMetadataInterface|string|null $transformer = null,
    ) {
    }

    public function getTransformer(): ?TransformerMetadataInterface
    {
        if (is_string($this->transformer)) {
            return new TransformerMetadata($this->transformer);
        }

        return $this->transformer;
    }
}
