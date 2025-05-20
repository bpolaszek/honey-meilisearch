<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Hydrater\PropertyTransformer;

use BenTools\ReflectionPlus\Reflection;
use Honey\Odm\Config\AsAttribute as AttributeMetadata;
use Honey\Odm\Criteria\Geo\Coordinates;
use Honey\Odm\Hydrater\PropertyTransformer\PropertyTransformerInterface;
use Honey\Odm\Misc\CoordinatesInterface;
use ReflectionNamedType;

use function ltrim;

final readonly class CoordinatesTransformer implements PropertyTransformerInterface
{
    public function supports(AttributeMetadata $metadata): bool
    {
        return $metadata->property->getSettableType() instanceof ReflectionNamedType
            && Reflection::isTypeCompatible($metadata->property->getSettableType(), CoordinatesInterface::class);
    }

    public function toObjectProperty(mixed $value, AttributeMetadata $metadata): ?CoordinatesInterface
    {
        if (null === $value) {
            return null;
        }

        $type = $metadata->property->getSettableType();
        if (!$type instanceof ReflectionNamedType) {
            throw new \LogicException("Property type is not a named type.");
        }

        $targetClass = ltrim($type->getName(), '?');
        if (!Reflection::class($targetClass)->isInstantiable()) {
            return Coordinates::make($value['lat'], $value['lng']);
        }

        /** @var CoordinatesInterface $coordinates */
        $coordinates = Reflection::class($targetClass)->newInstanceWithoutConstructor();
        $coordinates->latitude = $value['lat'];
        $coordinates->longitude = $value['lng'];

        return $coordinates;
    }

    /**
     * @param CoordinatesInterface $value
     */
    public function toDocumentAttribute(mixed $value, AttributeMetadata $metadata): ?array
    {
        if (null === $value) {
            return null;
        }

        return [
            'lat' => $value->latitude,
            'lng' => $value->longitude,
        ];
    }

}
