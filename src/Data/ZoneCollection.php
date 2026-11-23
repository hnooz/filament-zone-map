<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Data;

use ArrayIterator;
use Countable;
use Hnooz\FilamentZoneMap\Exceptions\InvalidZoneData;
use Hnooz\FilamentZoneMap\Support\GeoJsonValidator;
use Hnooz\FilamentZoneMap\Support\ValidationError;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonException;
use JsonSerializable;
use Traversable;

/**
 * Immutable set of zones, serialised as a GeoJSON FeatureCollection.
 *
 * @phpstan-import-type FeatureArray from Zone
 *
 * @phpstan-type FeatureCollectionArray array{type: 'FeatureCollection', features: list<FeatureArray>}
 *
 * @implements Arrayable<string, mixed>
 * @implements IteratorAggregate<int, Zone>
 */
final readonly class ZoneCollection implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param  list<Zone>  $zones
     */
    public function __construct(private array $zones = []) {}

    public static function empty(): self
    {
        return new self;
    }

    /**
     * Parse and structurally validate a FeatureCollection (array or JSON string).
     * Input limits are NOT applied here; use the ValidZoneCollection rule for user input.
     *
     * @param  array<array-key, mixed>|string|null  $data
     *
     * @throws InvalidZoneData
     */
    public static function fromArray(array|string|null $data): self
    {
        if ($data === null || $data === '') {
            return self::empty();
        }

        if (is_string($data)) {
            $data = self::decode($data);
        }

        $error = GeoJsonValidator::firstError($data);

        if ($error instanceof ValidationError) {
            throw InvalidZoneData::fromError($error);
        }

        /** @var list<array<string, mixed>> $features */
        $features = $data['features'];

        return new self(array_map(Zone::fromFeature(...), $features));
    }

    /**
     * @return list<Zone>
     */
    public function all(): array
    {
        return $this->zones;
    }

    public function find(string $id): ?Zone
    {
        foreach ($this->zones as $zone) {
            if ($zone->id === $id) {
                return $zone;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->zones);
    }

    public function isEmpty(): bool
    {
        return $this->zones === [];
    }

    /**
     * @return Traversable<int, Zone>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->zones);
    }

    /**
     * @return FeatureCollectionArray
     */
    public function toArray(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => array_map(static fn (Zone $zone): array => $zone->toFeature(), $this->zones),
        ];
    }

    /**
     * @return FeatureCollectionArray
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function decode(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw InvalidZoneData::invalidJson($e->getMessage());
        }

        return is_array($decoded) ? $decoded : throw InvalidZoneData::invalidJson('root is not an object');
    }
}
