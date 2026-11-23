<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Tests\Fixtures;

use Hnooz\FilamentZoneMap\Widgets\ZonesMapWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class TestZonesWidget extends ZonesMapWidget
{
    /** @var array<string, mixed> */
    public static array $options = [];

    public static int $limit = 200;

    protected ?string $heading = 'Coverage';

    protected ?string $labelColumn = 'name';

    protected function getMaxRecords(): int
    {
        return self::$limit;
    }

    /**
     * @return array<string, mixed>
     */
    public function viewDataForTesting(): array
    {
        return $this->getViewData();
    }

    protected function getZonesQuery(): Builder
    {
        return ZoneRecord::query()->orderBy('id');
    }

    protected function getRecordUrl(Model $record): ?string
    {
        return $record->getAttribute('name') === 'Evil' ? 'javascript:alert(1)' : "/zones/{$record->getKey()}";
    }

    protected function getMapOptions(): array
    {
        return self::$options;
    }
}
