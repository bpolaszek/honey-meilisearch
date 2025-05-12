<?php

namespace Honey\MeilisearchAdapter\Hydrater\PropertyTransformer;

use Honey\Odm\Config\AsAttribute as AttributeMetadata;
use Honey\Odm\Hydrater\PropertyTransformer\PropertyTransformerInterface;
use Honey\Odm\Misc\Reflection;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

use function sprintf;

final readonly class DateTimeTransformer implements PropertyTransformerInterface
{
    public function supports(AttributeMetadata $metadata): bool
    {
        return Reflection::isPropertyCompatible($metadata->property, DateTimeInterface::class);
    }

    public function toObjectProperty(mixed $value, AttributeMetadata $metadata): ?DateTimeInterface
    {
        if (null === $value) {
            return null;
        }

        /** @var class-string<DateTime|DateTimeImmutable> $className */
        $className = Reflection::getBestClassForProperty($metadata->property, [
            DateTimeImmutable::class,
            DateTime::class,
        ]);

        return $className::createFromTimestamp((int) $value);
    }

    public function toDocumentAttribute(mixed $value, AttributeMetadata $metadata): ?int
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        throw new InvalidArgumentException(
            sprintf("Expected an instance of DateTimeInterface, got %s", get_debug_type($value)),
        );
    }
}
