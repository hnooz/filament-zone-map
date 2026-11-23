<?php

declare(strict_types=1);

use Hnooz\FilamentZoneMap\Tests\Fixtures\GeoJson;
use Hnooz\FilamentZoneMap\Tests\Fixtures\TestZonesWidget;
use Hnooz\FilamentZoneMap\Tests\Fixtures\ZoneRecord;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    ZoneRecord::createTable();
    TestZonesWidget::$options = [];
    TestZonesWidget::$limit = 200;
});

function zoneRow(string $name, ?array $coverage): void
{
    DB::table('zone_records')->insert(['name' => $name, 'coverage' => $coverage === null ? null : json_encode($coverage)]);
}

it('renders the viewer inside a section', function (): void {
    zoneRow('North', GeoJson::collection([GeoJson::feature()]));

    livewire(TestZonesWidget::class)
        ->assertSuccessful()
        ->assertSee('Coverage')
        ->assertSeeHtml('zoneViewerComponent');
});

it('groups zones per record with label and url', function (): void {
    zoneRow('North', GeoJson::collection([GeoJson::feature()]));
    zoneRow('South', GeoJson::collection([GeoJson::circle()]));

    $groups = (new TestZonesWidget)->viewDataForTesting()['groups'];

    expect($groups)->toHaveCount(2)
        ->and($groups[0])->toMatchArray(['key' => '1', 'label' => 'North', 'url' => '/zones/1'])
        ->and($groups[1]['zones']['features'][0]['properties']['shape'])->toBe('circle');
});

it('drops unsafe urls', function (): void {
    zoneRow('Evil', GeoJson::collection([GeoJson::feature()]));

    expect((new TestZonesWidget)->viewDataForTesting()['groups'][0]['url'])->toBeNull();
});

it('skips records without zones or with invalid data', function (): void {
    zoneRow('Empty', null);
    zoneRow('NoFeatures', GeoJson::collection());
    DB::table('zone_records')->insert(['name' => 'Broken', 'coverage' => '{"type":"FeatureCollection","features":"nope"}']);
    zoneRow('Valid', GeoJson::collection([GeoJson::feature()]));

    $groups = (new TestZonesWidget)->viewDataForTesting()['groups'];

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['label'])->toBe('Valid');
});

it('respects maxRecords', function (): void {
    foreach (range(1, 5) as $i) {
        zoneRow("Zone {$i}", GeoJson::collection([GeoJson::feature()]));
    }

    TestZonesWidget::$limit = 3;

    expect((new TestZonesWidget)->viewDataForTesting()['groups'])->toHaveCount(3);
});

it('applies and sanitises map options', function (): void {
    TestZonesWidget::$options = ['height' => '640px', 'zoom' => 12, 'center' => [24.7, 46.6], 'zoneColor' => '#00ff00', 'fitBounds' => false];

    $data = (new TestZonesWidget)->viewDataForTesting();

    expect($data['height'])->toBe('640px')
        ->and($data['config'])->toMatchArray(['zoom' => 12, 'center' => ['lat' => 24.7, 'lng' => 46.6], 'zoneColor' => '#00FF00', 'fitBounds' => false]);

    TestZonesWidget::$options = ['height' => 'calc(100%); x: y'];

    expect((new TestZonesWidget)->viewDataForTesting()['height'])->toBe('480px');
});
