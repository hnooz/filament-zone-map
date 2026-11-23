<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Support;

/**
 * Validates a GeoJSON Polygon geometry: rings, positions, ranges and closure.
 */
final class PolygonValidator
{
    private const int MIN_RING_POSITIONS = 4;

    public static function firstError(mixed $geometry, ?int $maxVertices): ?ValidationError
    {
        if (! is_array($geometry) || ($geometry['type'] ?? null) !== 'Polygon') {
            return ValidationError::invalid('geometry.type must be "Polygon".');
        }

        $rings = $geometry['coordinates'] ?? null;

        if (! is_array($rings) || $rings === [] || ! array_is_list($rings)) {
            return ValidationError::invalid('geometry.coordinates must be a non-empty list of rings.');
        }

        $vertices = 0;

        foreach ($rings as $index => $ring) {
            $error = self::ringError($ring, $index);

            if ($error instanceof ValidationError) {
                return $error;
            }

            /** @var list<mixed> $ring */
            $vertices += count($ring) - 1;
        }

        if ($maxVertices !== null && $vertices > $maxVertices) {
            return new ValidationError(
                'max_vertices',
                "Polygon has {$vertices} vertices, limit is {$maxVertices}.",
                ['max' => $maxVertices],
            );
        }

        return null;
    }

    private static function ringError(mixed $ring, int $index): ?ValidationError
    {
        if (! is_array($ring) || ! array_is_list($ring) || count($ring) < self::MIN_RING_POSITIONS) {
            return ValidationError::invalid("Ring {$index} must be a list of at least 4 positions.");
        }

        foreach ($ring as $position) {
            if (! self::isValidPosition($position)) {
                return ValidationError::invalid("Ring {$index} has an invalid [lng, lat] position.");
            }
        }

        /** @var list<array{0: int|float, 1: int|float}> $ring */
        $first = $ring[0];
        $last = $ring[count($ring) - 1];

        if ((float) $first[0] !== (float) $last[0] || (float) $first[1] !== (float) $last[1]) {
            return ValidationError::invalid("Ring {$index} is not closed (first position must equal last).");
        }

        return null;
    }

    private static function isValidPosition(mixed $position): bool
    {
        if (! is_array($position) || ! array_is_list($position) || count($position) < 2) {
            return false;
        }

        [$lng, $lat] = $position;

        return self::isFiniteNumber($lng) && self::isFiniteNumber($lat)
            && $lng >= -180 && $lng <= 180
            && $lat >= -90 && $lat <= 90;
    }

    private static function isFiniteNumber(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value);
    }
}
