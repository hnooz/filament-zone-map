<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Casts\AsZoneCollection;
use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Exceptions\InvalidZoneData;
use Hnooz\FilamentZoneMap\Tests\Fixtures\GeoJson;
use Illuminate\Database\Eloquent\Model;

function zoneModel(): Model
{
    return new class extends Model
    {
        protected $guarded = [];

        protected function casts(): array
        {
            return ['coverage' => AsZoneCollection::class];
        }
    };
}

it('casts stored JSON to a ZoneCollection', function (): void {
    $model = zoneModel()->setRawAttributes(['coverage' => json_encode(GeoJson::collection([GeoJson::feature()]))]);

    expect($model->coverage)->toBeInstanceOf(ZoneCollection::class)
        ->and($model->coverage)->toHaveCount(1);
});

it('returns null for a null column', function (): void {
    expect(zoneModel()->setRawAttributes(['coverage' => null])->coverage)->toBeNull();
});

it('stores arrays, JSON strings and collections as normalised JSON', function (array|string|ZoneCollection $value): void {
    $model = zoneModel()->fill(['coverage' => $value]);

    $stored = json_decode((string) $model->getAttributes()['coverage'], true);

    expect($stored)->toBe(GeoJson::collection([GeoJson::feature()]));
})->with([
    'array' => fn () => GeoJson::collection([GeoJson::feature()]),
    'json string' => fn () => json_encode(GeoJson::collection([GeoJson::feature()])),
    'collection' => fn () => ZoneCollection::fromArray(GeoJson::collection([GeoJson::feature()])),
]);

it('stores null for null and empty collections', function (array|ZoneCollection|null $value): void {
    $model = zoneModel()->fill(['coverage' => $value]);

    expect($model->getAttributes()['coverage'])->toBeNull();
})->with([
    'null' => fn () => null,
    'empty collection' => fn () => ZoneCollection::empty(),
    'empty feature list' => fn () => GeoJson::collection(),
]);

it('rejects invalid input on set', function (): void {
    zoneModel()->fill(['coverage' => ['type' => 'FeatureCollection', 'features' => 'nope']]);
})->throws(InvalidZoneData::class);

it('rejects unsupported value types on set', function (): void {
    zoneModel()->fill(['coverage' => 42]);
})->throws(InvalidZoneData::class);

it('round-trips through the model', function (): void {
    $payload = GeoJson::collection([GeoJson::feature(), GeoJson::circle(['id' => 'zone-2'])]);

    $model = zoneModel()->fill(['coverage' => $payload]);
    $model->setRawAttributes($model->getAttributes());

    expect($model->coverage?->toArray())->toBe($payload);
});
