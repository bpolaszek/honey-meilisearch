<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Config;

use Honey\ODM\Core\Config\PlatformMetadataInterface;

/**
 * Meilisearch-specific property metadata, to be placed alongside the core #[AsField] attribute.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class AsAttribute implements PlatformMetadataInterface
{
    public function __construct(
        public ?bool $filterable = null,
        public ?bool $sortable = null,
    ) {
    }
}
