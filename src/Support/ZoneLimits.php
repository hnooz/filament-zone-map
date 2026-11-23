<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Support;

/**
 * Input limits enforced on user-submitted zones.
 *
 * A null limit means "not enforced". Structural parsing (e.g. reading from
 * the database) uses ZoneLimits::none() so stored data isn't rejected
 * when limits are tightened later.
 */
final readonly class ZoneLimits
{
    public function __construct(
        public ?int $maxZones = null,
        public ?int $maxVertices = null,
        public ?int $maxNameLength = null,
    ) {}

    public static function none(): self
    {
        return new self;
    }

    public static function fromConfig(): self
    {
        return new self(
            maxZones: self::intOrNull(config('filament-zone-map.limits.max_zones')),
            maxVertices: self::intOrNull(config('filament-zone-map.limits.max_vertices')),
            maxNameLength: self::intOrNull(config('filament-zone-map.limits.max_name_length')),
        );
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
