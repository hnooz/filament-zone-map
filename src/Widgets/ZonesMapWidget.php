<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Widgets;

use Filament\Widgets\Widget;
use Hnooz\FilamentZoneMap\Support\JsLabels;
use Hnooz\FilamentZoneMap\Support\MapSettings;
use Hnooz\FilamentZoneMap\Support\ZoneState;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shows the zones of many records on one map. Extend it:
 *
 *     class BranchCoverageMap extends ZonesMapWidget
 *     {
 *         protected string $zonesColumn = 'coverage';
 *         protected ?string $labelColumn = 'name';
 *
 *         protected function getZonesQuery(): Builder { return Branch::query(); }
 *         protected function getRecordUrl(Model $record): ?string { return BranchResource::getUrl('edit', ['record' => $record]); }
 *     }
 */
abstract class ZonesMapWidget extends Widget
{
    protected string $view = 'filament-zone-map::widgets.zones-map-widget';

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    /** Attribute holding the zones (cast with AsZoneCollection, array or JSON). */
    protected string $zonesColumn = 'coverage';

    /** Attribute shown in the info window, e.g. 'name'. Dot notation works for relations. */
    protected ?string $labelColumn = null;

    /** Upper bound of records loaded, to keep the page light. */
    protected int $maxRecords = 200;

    /**
     * @return Builder<covariant Model>
     */
    abstract protected function getZonesQuery(): Builder;

    protected function getMaxRecords(): int
    {
        return max(1, $this->maxRecords);
    }

    /** Link shown in the info window. Only relative and http(s) URLs are used. */
    protected function getRecordUrl(Model $record): ?string
    {
        return null;
    }

    /**
     * Map overrides; anything left out uses config/filament-zone-map.php.
     *
     * @return array{center?: array{0: float, 1: float}, zoom?: int, height?: string, mapId?: string, styles?: array{light?: array<int, mixed>, dark?: array<int, mixed>}, zoneColor?: string, fitBounds?: bool}
     */
    protected function getMapOptions(): array
    {
        return [];
    }

    protected function getHeading(): string|Htmlable|null
    {
        return $this->heading;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $options = $this->getMapOptions();

        return [
            'heading' => $this->getHeading(),
            'groups' => $this->getZoneGroups(),
            'config' => [...MapSettings::base($options), 'fitBounds' => $options['fitBounds'] ?? true],
            'labels' => JsLabels::viewer(),
            'height' => MapSettings::height($options['height'] ?? null),
        ];
    }

    /**
     * @return list<array{key: string, label: string|null, url: string|null, zones: array<string, mixed>}>
     */
    protected function getZoneGroups(): array
    {
        $records = $this->getZonesQuery()
            ->whereNotNull($this->zonesColumn)
            ->limit($this->getMaxRecords())
            ->get();

        $groups = [];

        foreach ($records as $record) {
            $zones = ZoneState::toDisplayArray($record->getAttribute($this->zonesColumn));

            if ($zones === null) {
                continue;
            }

            $label = $this->labelColumn !== null ? data_get($record, $this->labelColumn) : null;
            $key = $record->getKey();

            $groups[] = [
                'key' => is_scalar($key) ? (string) $key : (string) count($groups),
                'label' => is_scalar($label) ? (string) $label : null,
                'url' => MapSettings::safeUrl($this->getRecordUrl($record)),
                'zones' => $zones,
            ];
        }

        return $groups;
    }
}
