<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Support;

use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Exceptions\InvalidZoneData;

/**
 * Read-side normalisation for display components (entry, widget).
 */
final class ZoneState
{
    /**
     * ZoneCollection, array or JSON -> FeatureCollection array. Empty or invalid -> null.
     *
     * @return array{type: 'FeatureCollection', features: list<array<string, mixed>>}|null
     */
    public static function toDisplayArray(mixed $state): ?array
    {
        if (! $state instanceof ZoneCollection) {
            if (! is_array($state) && ! is_string($state)) {
                return null;
            }

            try {
                $state = ZoneCollection::fromArray($state);
            } catch (InvalidZoneData) {
                return null;
            }
        }

        return $state->isEmpty() ? null : $state->toArray();
    }
}
