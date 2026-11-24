<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Enums\ZoneShape;
use Hnooz\FilamentZoneMap\Forms\Components\ZoneMap;

it('defaults to all shapes and config limits', function (): void {
    $field = ZoneMap::make('coverage');

    expect($field->getShapes())->toBe(['polygon', 'rectangle', 'circle'])
        ->and($field->getMaxZones())->toBe(10)
        ->and($field->getMaxVertices())->toBe(500)
        ->and($field->hasEditableZones())->toBeFalse()
        ->and($field->getZoneColor())->toBe('#E74C3C')
        ->and($field->getHeight())->toBe('480px');
});

it('offers freehand only when opted in', function (): void {
    expect(ZoneMap::make('coverage')->getShapes())->not->toContain('freehand')
        ->and(ZoneMap::make('coverage')->shapes(['freehand', 'polygon'])->getShapes())->toBe(['polygon', 'freehand'])
        ->and(ZoneMap::make('coverage')->shapes([ZoneShape::Freehand])->getShapes())->toBe(['freehand']);
});

it('accepts shape enums, keeps canonical order and drops unknown shapes', function (): void {
    $field = ZoneMap::make('coverage')->shapes([ZoneShape::Circle, 'polygon', 'hexagon']);

    expect($field->getShapes())->toBe(['polygon', 'circle']);
});

it('limits to one zone when not multiple', function (): void {
    expect(ZoneMap::make('coverage')->multiple(false)->maxZones(5)->getMaxZones())->toBe(1);
});

it('treats non-positive limits as unlimited', function (): void {
    $field = ZoneMap::make('coverage')->maxZones(0)->maxVertices(-1);

    expect($field->getMaxZones())->toBeNull()
        ->and($field->getMaxVertices())->toBeNull();
});

it('sanitises colors, height and zoom', function (): void {
    $field = ZoneMap::make('coverage')
        ->colors(['#abcdef', 'red', '#ABCDEF', '#123456'])
        ->zoneColor('javascript:alert(1)')
        ->height('100px; background: url(x)')
        ->zoom(99);

    expect($field->getColors())->toBe(['#ABCDEF', '#123456'])
        ->and($field->getZoneColor())->toBe('#E74C3C')
        ->and($field->getHeight())->toBe('480px')
        ->and($field->getZoom())->toBe(22);
});

it('clamps the center to valid coordinates', function (): void {
    expect(ZoneMap::make('coverage')->center(120, -500)->getCenter())->toBe(['lat' => 90.0, 'lng' => -180.0]);
});

it('resolves map styling from the field, then config', function (): void {
    config()->set('filament-zone-map.map_id', 'from-config');
    config()->set('filament-zone-map.styles.dark', [['elementType' => 'geometry']]);

    expect(ZoneMap::make('coverage')->getMapId())->toBe('from-config')
        ->and(ZoneMap::make('coverage')->mapId('from-field')->getMapId())->toBe('from-field')
        ->and(ZoneMap::make('coverage')->getMapStyles())->toBe(['light' => null, 'dark' => [['elementType' => 'geometry']]])
        ->and(ZoneMap::make('coverage')->styles([['featureType' => 'poi']])->getMapStyles()['light'])->toBe([['featureType' => 'poi']]);
});

it('passes the browser key and locale to the base config', function (): void {
    config()->set('filament-zone-map.browser_key', 'test-key');
    app()->setLocale('ar');

    expect(ZoneMap::make('coverage')->getBaseMapConfig())
        ->toMatchArray(['key' => 'test-key', 'language' => 'ar', 'zoneColor' => '#E74C3C']);
});
