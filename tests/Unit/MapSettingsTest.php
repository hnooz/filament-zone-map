<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Support\MapSettings;

it('accepts only relative and http(s) urls', function (mixed $url, ?string $expected): void {
    expect(MapSettings::safeUrl($url))->toBe($expected);
})->with([
    'relative' => ['/zones/1', '/zones/1'],
    'https' => ['https://example.com/a', 'https://example.com/a'],
    'http upper' => ['HTTP://example.com', 'HTTP://example.com'],
    'javascript' => ['javascript:alert(1)', null],
    'data' => ['data:text/html,x', null],
    'protocol relative' => ['//evil.com', null],
    'empty' => ['', null],
    'not a string' => [42, null],
]);

it('sanitises css heights', function (mixed $height, string $expected): void {
    expect(MapSettings::height($height))->toBe($expected);
})->with([
    ['600px', '600px'],
    ['50vh', '50vh'],
    ['32.5rem', '32.5rem'],
    ['100%; background:red', '480px'],
    ['expression(x)', '480px'],
    [null, '480px'],
]);

it('normalises colors and falls back to the default', function (): void {
    expect(MapSettings::color('#abcdef'))->toBe('#ABCDEF')
        ->and(MapSettings::color('red'))->toBe('#E74C3C')
        ->and(MapSettings::isColor('#12345'))->toBeFalse();
});

it('clamps center and zoom', function (): void {
    expect(MapSettings::center(95, 200))->toBe(['lat' => 90.0, 'lng' => 180.0])
        ->and(MapSettings::center('x', null))->toBe(['lat' => 0.0, 'lng' => 0.0])
        ->and(MapSettings::zoom(30))->toBe(22)
        ->and(MapSettings::zoom(-1))->toBe(0);
});

it('builds the base config with overrides and config fallbacks', function (): void {
    config()->set('filament-zone-map.browser_key', 'k');
    config()->set('filament-zone-map.map_id', 'cfg-map');

    expect(MapSettings::base(['zoom' => 9, 'center' => [24.7, 46.6]]))->toMatchArray([
        'key' => 'k',
        'zoom' => 9,
        'center' => ['lat' => 24.7, 'lng' => 46.6],
        'mapId' => 'cfg-map',
        'styles' => ['light' => null, 'dark' => null],
        'zoneColor' => '#E74C3C',
    ]);
});
