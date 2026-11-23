<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Casts;

use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Exceptions\InvalidZoneData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a JSON column to a ZoneCollection.
 *
 * - NULL column        -> null
 * - empty collection   -> stored as NULL (no "empty FeatureCollection" rows)
 * - invalid input      -> InvalidZoneData (structure only; limits belong to validation)
 *
 * Set accepts mixed on purpose: Eloquent passes any user value, and it is validated here.
 *
 * @implements CastsAttributes<ZoneCollection, mixed>
 */
final class AsZoneCollection implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidZoneData
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ZoneCollection
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) && ! is_array($value)) {
            throw InvalidZoneData::invalidJson('unexpected column type '.get_debug_type($value));
        }

        return ZoneCollection::fromArray($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidZoneData
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $zones = match (true) {
            $value instanceof ZoneCollection => $value,
            is_array($value), is_string($value) => ZoneCollection::fromArray($value),
            default => throw InvalidZoneData::invalidJson('unsupported value type '.get_debug_type($value)),
        };

        return $zones->isEmpty() ? null : $zones->toJson();
    }
}
