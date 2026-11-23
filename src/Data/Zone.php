<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Data;

use Hnooz\FilamentZoneMap\Enums\ZoneShape;
use Illuminate\Support\Str;

/**
 * One zone: a GeoJSON Polygon Feature with our properties contract.
 *
 * Build it through ZoneCollection::fromArray(), which validates first.
 * Coordinates are [lng, lat] as GeoJSON requires.
 *
 * @phpstan-type Position array{0: float, 1: float}
 * @phpstan-type Ring list<Position>
 * @phpstan-type FeatureArray array{
 *     type: 'Feature',
 *     id: string,
 *     geometry: array{type: 'Polygon', coordinates: list<Ring>},
 *     properties: array{shape: string, name: ?string, color: ?string, radius_m: ?float}
 * }
 */
final readonly class Zone
{
    /**
     * @param  list<Ring>  $rings
     */
    public function __construct(
        public string $id,
        public ZoneShape $shape,
        public array $rings,
        public ?string $name = null,
        public ?string $color = null,
        public ?float $radiusMeters = null,
    ) {}

    /**
     * @param  array<string, mixed>  $feature  A feature that already passed GeoJsonValidator.
     */
    public static function fromFeature(array $feature): self
    {
        /** @var array<string, mixed> $properties */
        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];

        /** @var array{coordinates: list<list<array{0: int|float, 1: int|float}>>} $geometry */
        $geometry = $feature['geometry'];

        $id = $feature['id'] ?? null;
        $shape = $properties['shape'] ?? null;
        $name = $properties['name'] ?? null;
        $color = $properties['color'] ?? null;
        $radius = $properties['radius_m'] ?? null;

        return new self(
            id: is_string($id) || is_int($id) ? (string) $id : (string) Str::uuid(),
            shape: ZoneShape::tryFrom(is_string($shape) ? $shape : '') ?? ZoneShape::Polygon,
            rings: self::normalizeRings($geometry['coordinates']),
            name: is_string($name) ? $name : null,
            color: is_string($color) ? strtoupper($color) : null,
            radiusMeters: is_int($radius) || is_float($radius) ? (float) $radius : null,
        );
    }

    /**
     * @return FeatureArray
     */
    public function toFeature(): array
    {
        return [
            'type' => 'Feature',
            'id' => $this->id,
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => $this->rings,
            ],
            'properties' => [
                'shape' => $this->shape->value,
                'name' => $this->name,
                'color' => $this->color,
                'radius_m' => $this->radiusMeters,
            ],
        ];
    }

    /**
     * Number of distinct vertices across all rings (closing points excluded).
     */
    public function vertexCount(): int
    {
        return array_sum(array_map(static fn (array $ring): int => count($ring) - 1, $this->rings));
    }

    /**
     * @param  list<list<array{0: int|float, 1: int|float}>>  $rings
     * @return list<Ring>
     */
    private static function normalizeRings(array $rings): array
    {
        return array_map(
            static fn (array $ring): array => array_map(
                static fn (array $position): array => [(float) $position[0], (float) $position[1]],
                $ring,
            ),
            $rings,
        );
    }
}
