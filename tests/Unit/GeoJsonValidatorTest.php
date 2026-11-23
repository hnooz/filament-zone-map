<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Support\GeoJsonValidator;
use Hnooz\FilamentZoneMap\Support\ZoneLimits;
use Hnooz\FilamentZoneMap\Tests\Fixtures\GeoJson;

it('accepts valid payloads', function (array $payload): void {
    expect(GeoJsonValidator::firstError($payload))->toBeNull();
})->with([
    'empty collection' => fn () => GeoJson::collection(),
    'polygon' => fn () => GeoJson::collection([GeoJson::feature()]),
    'circle' => fn () => GeoJson::collection([GeoJson::circle()]),
    'rectangle' => fn () => GeoJson::collection([GeoJson::feature(properties: ['shape' => 'rectangle'])]),
    'no id / no properties' => fn () => GeoJson::collection([['type' => 'Feature', 'geometry' => ['type' => 'Polygon', 'coordinates' => GeoJson::squareRings()]]]),
    'null name and color' => fn () => GeoJson::collection([GeoJson::feature(properties: ['name' => null, 'color' => null])]),
    'integer coordinates' => fn () => GeoJson::withGeometry([[[46, 24], [47, 24], [47, 25], [46, 24]]]),
    'polygon with hole' => fn () => GeoJson::withGeometry([...GeoJson::squareRings(), [[46.68, 24.72], [46.69, 24.72], [46.69, 24.73], [46.68, 24.72]]]),
    'boundary coordinates' => fn () => GeoJson::withGeometry([[[-180, -90], [180, -90], [180, 90], [-180, -90]]]),
]);

it('rejects invalid structure', function (array|string|null $payload): void {
    expect(GeoJsonValidator::firstError($payload)?->key)->toBe('invalid');
})->with([
    'null' => [null],
    'string' => ['FeatureCollection'],
    'wrong root type' => fn () => ['type' => 'Feature', 'features' => []],
    'features not a list' => fn () => ['type' => 'FeatureCollection', 'features' => ['a' => GeoJson::feature()]],
    'feature wrong type' => fn () => GeoJson::collection([GeoJson::feature(['type' => 'Point'])]),
    'geometry not polygon' => fn () => GeoJson::collection([GeoJson::feature(['geometry' => ['type' => 'MultiPolygon', 'coordinates' => []]])]),
    'empty id' => fn () => GeoJson::collection([GeoJson::feature(['id' => ''])]),
    'id too long' => fn () => GeoJson::collection([GeoJson::feature(['id' => str_repeat('x', 65)])]),
    'duplicate ids' => fn () => GeoJson::collection([GeoJson::feature(), GeoJson::feature()]),
    'unknown shape' => fn () => GeoJson::collection([GeoJson::feature(properties: ['shape' => 'hexagon'])]),
    'non-string name' => fn () => GeoJson::collection([GeoJson::feature(properties: ['name' => ['x']])]),
    'circle without radius' => fn () => GeoJson::collection([GeoJson::feature(properties: ['shape' => 'circle'])]),
    'circle with zero radius' => fn () => GeoJson::collection([GeoJson::feature(properties: ['shape' => 'circle', 'radius_m' => 0])]),
    'radius on polygon' => fn () => GeoJson::collection([GeoJson::feature(properties: ['radius_m' => 100])]),
]);

it('rejects invalid geometry', function (array $coordinates): void {
    expect(GeoJsonValidator::firstError(GeoJson::withGeometry($coordinates))?->key)->toBe('invalid');
})->with([
    'no rings' => [[]],
    'ring too short' => [[[[46.67, 24.71], [46.70, 24.71], [46.67, 24.71]]]],
    'unclosed ring' => [[[[46.67, 24.71], [46.70, 24.71], [46.70, 24.74], [46.67, 24.74]]]],
    'latitude out of range' => [[[[46.67, 91], [46.70, 24.71], [46.70, 24.74], [46.67, 91]]]],
    'longitude out of range' => [[[[181, 24.71], [46.70, 24.71], [46.70, 24.74], [181, 24.71]]]],
    'string coordinate' => [[[['46.67', 24.71], [46.70, 24.71], [46.70, 24.74], ['46.67', 24.71]]]],
    'position with one value' => [[[[46.67], [46.70, 24.71], [46.70, 24.74], [46.67]]]],
    'ring is an object' => [[['a' => [46.67, 24.71]]]],
]);

it('enforces input limits', function (array $payload, ZoneLimits $limits, string $key, int $max): void {
    $error = GeoJsonValidator::firstError($payload, $limits);

    expect($error?->key)->toBe($key)
        ->and($error?->replace)->toBe(['max' => $max]);
})->with([
    'max zones' => [fn () => GeoJson::collection([GeoJson::feature(['id' => 'a']), GeoJson::feature(['id' => 'b'])]), new ZoneLimits(maxZones: 1), 'max_zones', 1],
    'max vertices' => [fn () => GeoJson::withGeometry(GeoJson::ringWithVertices(6)), new ZoneLimits(maxVertices: 5), 'max_vertices', 5],
    'name length' => [fn () => GeoJson::collection([GeoJson::feature(properties: ['name' => str_repeat('a', 11)])]), new ZoneLimits(maxNameLength: 10), 'name_too_long', 10],
]);

it('counts vertices without closing points', function (): void {
    $payload = GeoJson::withGeometry(GeoJson::ringWithVertices(5));

    expect(GeoJsonValidator::firstError($payload, new ZoneLimits(maxVertices: 5)))->toBeNull();
});

it('rejects invalid colors', function (mixed $color): void {
    $payload = GeoJson::collection([GeoJson::feature(properties: ['color' => $color])]);

    expect(GeoJsonValidator::firstError($payload)?->key)->toBe('invalid_color');
})->with(['red', '#FFF', '#GGGGGG', '#E74C3C;', 123]);

it('measures name length in characters, not bytes', function (): void {
    $payload = GeoJson::collection([GeoJson::feature(properties: ['name' => 'الشمال'])]);

    expect(GeoJsonValidator::firstError($payload, new ZoneLimits(maxNameLength: 6)))->toBeNull();
});
