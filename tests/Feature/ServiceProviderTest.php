<?php

declare(strict_types=1);

use Filament\Support\Facades\FilamentAsset;
use Hnooz\FilamentZoneMap\FilamentZoneMapPlugin;
use Hnooz\FilamentZoneMap\FilamentZoneMapServiceProvider;

it('merges the package config', function (): void {
    expect(config('filament-zone-map.defaults.zoom'))->toBe(2)
        ->and(config('filament-zone-map.limits.max_zones'))->toBe(10)
        ->and(config('filament-zone-map.colors'))->toBeArray()->not->toBeEmpty();
});

it('registers the alpine components', function (string $component): void {
    $src = FilamentAsset::getAlpineComponentSrc($component, FilamentZoneMapServiceProvider::ASSET_PACKAGE);

    expect($src)->toBeString()->toContain($component);
})->with(['zone-map', 'zone-viewer']);

it('loads the package translations', function (): void {
    expect(__('filament-zone-map::zone-map.modes.polygon'))->toBe('Polygon');
});

it('exposes a stable plugin id', function (): void {
    expect(FilamentZoneMapPlugin::make()->getId())->toBe('filament-zone-map');
});
