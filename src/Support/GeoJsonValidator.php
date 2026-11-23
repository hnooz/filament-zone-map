<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Support;

use Hnooz\FilamentZoneMap\Enums\ZoneShape;

/**
 * Validates a zone payload (GeoJSON FeatureCollection) against the data contract.
 *
 * Single source of truth: used by the validation rule, the value objects and the cast.
 */
final class GeoJsonValidator
{
    public const string COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    private const int MAX_ID_LENGTH = 64;

    public static function firstError(mixed $value, ?ZoneLimits $limits = null): ?ValidationError
    {
        $limits ??= ZoneLimits::none();

        if (! is_array($value) || ($value['type'] ?? null) !== 'FeatureCollection') {
            return ValidationError::invalid('Root must be a FeatureCollection object.');
        }

        $features = $value['features'] ?? null;

        if (! is_array($features) || ! array_is_list($features)) {
            return ValidationError::invalid('features must be a list.');
        }

        if ($limits->maxZones !== null && count($features) > $limits->maxZones) {
            return new ValidationError(
                'max_zones',
                'Too many zones: '.count($features).", limit is {$limits->maxZones}.",
                ['max' => $limits->maxZones],
            );
        }

        $ids = [];

        foreach ($features as $feature) {
            $error = self::featureError($feature, $limits);

            if ($error instanceof ValidationError) {
                return $error;
            }

            /** @var array<string, mixed> $feature */
            if (isset($feature['id'])) {
                $id = (string) $feature['id'];

                if (isset($ids[$id])) {
                    return ValidationError::invalid("Duplicate feature id \"{$id}\".");
                }

                $ids[$id] = true;
            }
        }

        return null;
    }

    private static function featureError(mixed $feature, ZoneLimits $limits): ?ValidationError
    {
        if (! is_array($feature) || ($feature['type'] ?? null) !== 'Feature') {
            return ValidationError::invalid('Each item in features must be a Feature.');
        }

        $id = $feature['id'] ?? null;

        if ($id !== null && ! self::isValidId($id)) {
            return ValidationError::invalid('Feature id must be a non-empty string or integer (max 64 chars).');
        }

        return PolygonValidator::firstError($feature['geometry'] ?? null, $limits->maxVertices)
            ?? self::propertiesError($feature['properties'] ?? [], $limits);
    }

    private static function propertiesError(mixed $properties, ZoneLimits $limits): ?ValidationError
    {
        if (! is_array($properties)) {
            return ValidationError::invalid('properties must be an object.');
        }

        $shape = ZoneShape::tryFrom(is_string($properties['shape'] ?? null) ? $properties['shape'] : 'polygon');

        if (! $shape instanceof ZoneShape) {
            return ValidationError::invalid('properties.shape must be one of: '.implode(', ', ZoneShape::values()).'.');
        }

        $name = $properties['name'] ?? null;

        if ($name !== null && ! is_string($name)) {
            return ValidationError::invalid('properties.name must be a string or null.');
        }

        if (is_string($name) && $limits->maxNameLength !== null && mb_strlen($name) > $limits->maxNameLength) {
            return new ValidationError('name_too_long', 'Zone name is too long.', ['max' => $limits->maxNameLength]);
        }

        $color = $properties['color'] ?? null;

        if ($color !== null && (! is_string($color) || preg_match(self::COLOR_PATTERN, $color) !== 1)) {
            return new ValidationError('invalid_color', 'properties.color must be a #RRGGBB hex value.');
        }

        return self::radiusError($shape, $properties['radius_m'] ?? null);
    }

    private static function radiusError(ZoneShape $shape, mixed $radius): ?ValidationError
    {
        $isPositive = (is_int($radius) || is_float($radius)) && is_finite((float) $radius) && $radius > 0;

        if ($shape === ZoneShape::Circle && ! $isPositive) {
            return ValidationError::invalid('properties.radius_m must be a positive number for circles.');
        }

        if ($shape !== ZoneShape::Circle && $radius !== null) {
            return ValidationError::invalid('properties.radius_m is only allowed for circles.');
        }

        return null;
    }

    private static function isValidId(mixed $id): bool
    {
        return (is_int($id) || (is_string($id) && $id !== '')) && mb_strlen((string) $id) <= self::MAX_ID_LENGTH;
    }
}
