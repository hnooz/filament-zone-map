<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Data\Zone;
use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Enums\ZoneShape;
use Hnooz\FilamentZoneMap\Exceptions\InvalidZoneData;
use Hnooz\FilamentZoneMap\Tests\Fixtures\GeoJson;

it('parses a feature collection into zones', function (): void {
    $zones = ZoneCollection::fromArray(GeoJson::collection([GeoJson::feature(), GeoJson::circle(['id' => 'zone-2'])]));

    expect($zones)->toHaveCount(2)
        ->and($zones->isEmpty())->toBeFalse()
        ->and($zones->find('zone-1'))->toBeInstanceOf(Zone::class)
        ->and($zones->find('zone-2')?->shape)->toBe(ZoneShape::Circle)
        ->and($zones->find('zone-2')?->radiusMeters)->toBe(1500.0)
        ->and($zones->find('missing'))->toBeNull();
});

it('round-trips without losing data', function (): void {
    $payload = GeoJson::collection([GeoJson::feature(), GeoJson::circle(['id' => 'zone-2'])]);

    expect(ZoneCollection::fromArray($payload)->toArray())->toBe($payload);
});

it('maps the freehand shape', function (): void {
    $zones = ZoneCollection::fromArray(GeoJson::collection([GeoJson::feature(properties: ['shape' => 'freehand'])]));

    expect($zones->all()[0]->shape)->toBe(ZoneShape::Freehand)
        ->and($zones->toArray()['features'][0]['properties']['shape'])->toBe('freehand');
});

it('accepts a JSON string', function (): void {
    $json = json_encode(GeoJson::collection([GeoJson::feature()]), JSON_THROW_ON_ERROR);

    expect(ZoneCollection::fromArray($json))->toHaveCount(1);
});

it('returns an empty collection for null and empty string', function (?string $input): void {
    expect(ZoneCollection::fromArray($input)->isEmpty())->toBeTrue();
})->with([null, '']);

it('normalises coordinates to floats and colors to upper case', function (): void {
    $payload = GeoJson::withGeometry([[[46, 24], [47, 24], [47, 25], [46, 24]]]);
    $payload['features'][0]['properties']['color'] = '#e74c3c';

    $zone = ZoneCollection::fromArray($payload)->all()[0];

    expect($zone->rings[0][0])->toBe([46.0, 24.0])
        ->and($zone->color)->toBe('#E74C3C')
        ->and($zone->vertexCount())->toBe(3);
});

it('generates an id and defaults the shape when missing', function (): void {
    $feature = ['type' => 'Feature', 'geometry' => ['type' => 'Polygon', 'coordinates' => GeoJson::squareRings()]];

    $zone = ZoneCollection::fromArray(GeoJson::collection([$feature]))->all()[0];

    expect($zone->id)->toBeUuid()
        ->and($zone->shape)->toBe(ZoneShape::Polygon)
        ->and($zone->name)->toBeNull();
});

it('casts integer ids to strings', function (): void {
    $zone = ZoneCollection::fromArray(GeoJson::collection([GeoJson::feature(['id' => 7])]))->all()[0];

    expect($zone->id)->toBe('7');
});

it('serialises to JSON with float precision kept', function (): void {
    $json = ZoneCollection::fromArray(GeoJson::withGeometry([[[46, 24], [47, 24], [47, 25], [46, 24]]]))->toJson();

    expect($json)->toContain('[46.0,24.0]')
        ->and(json_decode($json, true))->toMatchArray(['type' => 'FeatureCollection']);
});

it('is iterable and json serialisable', function (): void {
    $zones = ZoneCollection::fromArray(GeoJson::collection([GeoJson::feature()]));

    expect(iterator_to_array($zones))->toHaveCount(1)
        ->and(json_decode((string) json_encode($zones), true))->toEqual($zones->toArray());
});

it('throws on invalid input', function (array|string $input): void {
    ZoneCollection::fromArray($input);
})->with([
    'malformed json' => ['{"type":'],
    'json scalar' => ['"text"'],
    'wrong structure' => [['type' => 'FeatureCollection', 'features' => 'nope']],
    'unclosed ring' => fn () => GeoJson::withGeometry([[[46.67, 24.71], [46.70, 24.71], [46.70, 24.74], [46.67, 24.74]]]),
])->throws(InvalidZoneData::class);

it('does not apply input limits when parsing', function (): void {
    $features = array_map(fn (int $i): array => GeoJson::feature(['id' => "z{$i}"]), range(1, 20));

    expect(ZoneCollection::fromArray(GeoJson::collection($features)))->toHaveCount(20);
});
