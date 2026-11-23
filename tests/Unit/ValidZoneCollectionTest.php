<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Rules\ValidZoneCollection;
use Hnooz\FilamentZoneMap\Support\ZoneLimits;
use Hnooz\FilamentZoneMap\Tests\Fixtures\GeoJson;
use Illuminate\Support\Facades\Validator;

function validateZones(mixed $value, ?ZoneLimits $limits = null): Illuminate\Validation\Validator
{
    return Validator::make(['coverage' => $value], ['coverage' => [new ValidZoneCollection($limits)]]);
}

it('passes valid zones in every accepted format', function (array|string|ZoneCollection|null $value): void {
    expect(validateZones($value)->passes())->toBeTrue();
})->with([
    'array' => fn () => GeoJson::collection([GeoJson::feature()]),
    'json string' => fn () => json_encode(GeoJson::collection([GeoJson::feature()])),
    'zone collection' => fn () => ZoneCollection::fromArray(GeoJson::collection([GeoJson::feature()])),
    'null' => fn () => null,
]);

it('fails with a translated message', function (): void {
    $validator = validateZones(GeoJson::withGeometry([[[46.67, 24.71], [46.70, 24.71], [46.70, 24.74], [46.67, 24.74]]]));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('coverage'))->toBe('The coverage must contain valid zones.');
});

it('fails on malformed json', function (): void {
    expect(validateZones('{"type":')->fails())->toBeTrue();
});

it('includes the limit in the message', function (): void {
    $value = GeoJson::collection([GeoJson::feature(['id' => 'a']), GeoJson::feature(['id' => 'b'])]);

    $validator = validateZones($value, new ZoneLimits(maxZones: 1));

    expect($validator->errors()->first('coverage'))->toBe('The coverage may not have more than 1 zones.');
});

it('reads limits from config by default', function (): void {
    config()->set('filament-zone-map.limits.max_vertices', 5);

    $validator = validateZones(GeoJson::withGeometry(GeoJson::ringWithVertices(6)));

    expect($validator->errors()->first('coverage'))->toBe('Each zone in coverage may not have more than 5 points.');
});

it('rejects invalid colors with a dedicated message', function (): void {
    $validator = validateZones(GeoJson::collection([GeoJson::feature(properties: ['color' => 'red'])]));

    expect($validator->errors()->first('coverage'))->toContain('valid hex colors');
});
