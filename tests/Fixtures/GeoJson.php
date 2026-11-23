<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Tests\Fixtures;

/**
 * Builders for GeoJSON test payloads. Coordinates are [lng, lat] around Riyadh.
 */
final class GeoJson
{
    /**
     * @return list<list<array{0: float, 1: float}>>
     */
    public static function squareRings(): array
    {
        return [[
            [46.67, 24.71],
            [46.70, 24.71],
            [46.70, 24.74],
            [46.67, 24.74],
            [46.67, 24.71],
        ]];
    }

    /**
     * @param  array<string, mixed>  $overrides  Top-level feature keys.
     * @param  array<string, mixed>  $properties  Merged into default properties.
     * @return array<string, mixed>
     */
    public static function feature(array $overrides = [], array $properties = []): array
    {
        return array_replace([
            'type' => 'Feature',
            'id' => 'zone-1',
            'geometry' => ['type' => 'Polygon', 'coordinates' => self::squareRings()],
            'properties' => array_replace([
                'shape' => 'polygon',
                'name' => 'North',
                'color' => '#E74C3C',
                'radius_m' => null,
            ], $properties),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function circle(array $overrides = []): array
    {
        return self::feature($overrides, ['shape' => 'circle', 'radius_m' => 1500.0]);
    }

    /**
     * @param  list<mixed>  $features
     * @return array<string, mixed>
     */
    public static function collection(array $features = []): array
    {
        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    /**
     * @return array<string, mixed>
     */
    public static function withGeometry(mixed $coordinates): array
    {
        return self::collection([
            self::feature(['geometry' => ['type' => 'Polygon', 'coordinates' => $coordinates]]),
        ]);
    }

    /**
     * Polygon ring with $vertices distinct points (plus the closing point).
     *
     * @return list<list<array{0: float, 1: float}>>
     */
    public static function ringWithVertices(int $vertices): array
    {
        $ring = [];

        for ($i = 0; $i < $vertices; $i++) {
            $angle = 2 * M_PI * $i / $vertices;
            $ring[] = [round(46.68 + 0.01 * cos($angle), 6), round(24.72 + 0.01 * sin($angle), 6)];
        }

        $ring[] = $ring[0];

        return [$ring];
    }
}
