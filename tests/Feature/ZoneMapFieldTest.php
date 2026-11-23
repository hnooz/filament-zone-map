<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Forms\Components\ZoneMap;
use Hnooz\FilamentZoneMap\Tests\Fixtures\GeoJson;
use Hnooz\FilamentZoneMap\Tests\Fixtures\ZoneFormPage;

use function Pest\Livewire\livewire;

beforeEach(fn () => ZoneFormPage::resetFixture());

it('renders the map, toolbar and config', function (): void {
    ZoneFormPage::$configureField = fn (ZoneMap $field): ZoneMap => $field->shapes(['polygon'])->height('300px');

    livewire(ZoneFormPage::class)
        ->assertSuccessful()
        ->assertSeeHtml('fi-zone-map-canvas')
        ->assertSeeHtml('fi-zone-map-toolbar')
        ->assertSeeHtml('zoneMapFormComponent')
        ->assertSeeHtml('--fi-zone-map-height: 300px')
        ->assertSeeHtml('wire:ignore');
});

it('hides the toolbar and editor when disabled', function (): void {
    ZoneFormPage::$configureField = fn (ZoneMap $field): ZoneMap => $field->editableZones()->disabled();

    livewire(ZoneFormPage::class)
        ->assertSuccessful()
        ->assertDontSeeHtml('fi-zone-map-toolbar')
        ->assertDontSeeHtml('fi-zone-map-editor');
});

it('renders the zone editor when enabled', function (): void {
    ZoneFormPage::$configureField = fn (ZoneMap $field): ZoneMap => $field->editableZones();

    livewire(ZoneFormPage::class)->assertSeeHtml('fi-zone-map-editor');
});

it('hydrates state from a ZoneCollection, JSON or array', function (ZoneCollection|string|array $initial): void {
    ZoneFormPage::$initialState = $initial;

    livewire(ZoneFormPage::class)->assertSet('data.coverage', GeoJson::collection([GeoJson::feature()]));
})->with([
    'collection' => fn () => ZoneCollection::fromArray(GeoJson::collection([GeoJson::feature()])),
    'json' => fn () => json_encode(GeoJson::collection([GeoJson::feature()])),
    'array' => fn () => GeoJson::collection([GeoJson::feature()]),
]);

it('hydrates empty values to null', function (ZoneCollection|array|null $initial): void {
    ZoneFormPage::$initialState = $initial;

    livewire(ZoneFormPage::class)->assertSet('data.coverage', null);
})->with([
    'null' => fn () => null,
    'empty collection' => fn () => GeoJson::collection(),
    'empty ZoneCollection' => fn () => ZoneCollection::empty(),
]);

it('saves normalised zones', function (): void {
    $payload = GeoJson::collection([GeoJson::feature(properties: ['color' => '#e74c3c'])]);

    $page = livewire(ZoneFormPage::class)
        ->set('data.coverage', $payload)
        ->call('save')
        ->assertHasNoErrors();

    expect($page->get('saved.coverage.features.0.properties.color'))->toBe('#E74C3C');
});

it('dehydrates an empty collection to null', function (): void {
    livewire(ZoneFormPage::class)
        ->set('data.coverage', GeoJson::collection())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved.coverage', null);
});

it('rejects invalid zones', function (): void {
    livewire(ZoneFormPage::class)
        ->set('data.coverage', GeoJson::withGeometry([[[46.67, 24.71], [46.70, 24.71], [46.70, 24.74], [46.67, 24.74]]]))
        ->call('save')
        ->assertHasErrors(['data.coverage']);
});

it('requires at least one zone when required', function (?array $value): void {
    ZoneFormPage::$configureField = fn (ZoneMap $field): ZoneMap => $field->required();

    $page = livewire(ZoneFormPage::class)
        ->set('data.coverage', $value)
        ->call('save')
        ->assertHasErrors(['data.coverage']);

    expect($page->errors()->first('data.coverage'))->toContain('required');
})->with([
    'null' => fn () => null,
    'empty collection' => fn () => GeoJson::collection(),
]);

it('allows a single zone with multiple(false)', function (): void {
    ZoneFormPage::$configureField = fn (ZoneMap $field): ZoneMap => $field->multiple(false);

    livewire(ZoneFormPage::class)
        ->set('data.coverage', GeoJson::collection([GeoJson::feature(['id' => 'a']), GeoJson::feature(['id' => 'b'])]))
        ->call('save')
        ->assertHasErrors(['data.coverage']);
});

it('enforces maxVertices from the field', function (): void {
    ZoneFormPage::$configureField = fn (ZoneMap $field): ZoneMap => $field->maxVertices(5);

    livewire(ZoneFormPage::class)
        ->set('data.coverage', GeoJson::withGeometry(GeoJson::ringWithVertices(6)))
        ->call('save')
        ->assertHasErrors(['data.coverage']);
});
