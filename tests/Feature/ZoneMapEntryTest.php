<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Infolists\Components\ZoneMapEntry;
use Hnooz\FilamentZoneMap\Tests\Fixtures\GeoJson;
use Hnooz\FilamentZoneMap\Tests\Fixtures\ZoneInfolistPage;

use function Pest\Livewire\livewire;

beforeEach(fn () => ZoneInfolistPage::resetFixture());

it('renders the read-only viewer for stored zones', function (): void {
    ZoneInfolistPage::$coverage = json_encode(GeoJson::collection([GeoJson::feature()]));

    livewire(ZoneInfolistPage::class)
        ->assertSuccessful()
        ->assertSeeHtml('zoneViewerComponent')
        ->assertSeeHtml('fi-zone-map-canvas')
        ->assertDontSeeHtml('fi-zone-map-toolbar');
});

it('applies the configured height', function (): void {
    ZoneInfolistPage::$coverage = json_encode(GeoJson::collection([GeoJson::feature()]));
    ZoneInfolistPage::$configureEntry = fn (ZoneMapEntry $entry): ZoneMapEntry => $entry->height('320px');

    livewire(ZoneInfolistPage::class)->assertSeeHtml('--fi-zone-map-height: 320px');
});

it('shows the placeholder when there are no zones', function (): void {
    ZoneInfolistPage::$configureEntry = fn (ZoneMapEntry $entry): ZoneMapEntry => $entry->placeholder('No zones yet');

    livewire(ZoneInfolistPage::class)
        ->assertSee('No zones yet')
        ->assertDontSeeHtml('zoneViewerComponent');
});

it('renders an empty map without a placeholder', function (): void {
    livewire(ZoneInfolistPage::class)->assertSeeHtml('zoneViewerComponent');
});

it('builds one group from the record zones', function (): void {
    $entry = ZoneMapEntry::make('coverage')->constantState(json_encode(GeoJson::collection([GeoJson::feature()])));

    expect($entry->getZoneGroups())->toBe([[
        'key' => 'entry',
        'label' => null,
        'url' => null,
        'zones' => GeoJson::collection([GeoJson::feature()]),
    ]]);
});

it('returns no groups for invalid or empty data', function (mixed $state): void {
    expect(ZoneMapEntry::make('coverage')->constantState($state)->getZoneGroups())->toBe([]);
})->with([
    'null' => [null],
    'malformed json' => ['{"type":'],
    'empty collection' => [['type' => 'FeatureCollection', 'features' => []]],
]);

it('passes fitBounds to the viewer config', function (): void {
    expect(ZoneMapEntry::make('coverage')->getViewerConfig()['fitBounds'])->toBeTrue()
        ->and(ZoneMapEntry::make('coverage')->fitBounds(false)->getViewerConfig()['fitBounds'])->toBeFalse();
});
