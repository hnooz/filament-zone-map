<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Infolists\Components;

use Closure;
use Filament\Infolists\Components\Entry;
use Hnooz\FilamentZoneMap\Concerns\HasMapConfiguration;
use Hnooz\FilamentZoneMap\Support\JsLabels;
use Hnooz\FilamentZoneMap\Support\ZoneState;

/**
 * Read-only map of a record's zones. Click a zone to see its name.
 */
final class ZoneMapEntry extends Entry
{
    use HasMapConfiguration;

    protected string $view = 'filament-zone-map::infolists.zone-map-entry';

    protected bool|Closure $shouldFitBounds = true;

    /** Zoom the map to the zones (default). Turn off to keep the configured center and zoom. */
    public function fitBounds(bool|Closure $condition = true): static
    {
        $this->shouldFitBounds = $condition;

        return $this;
    }

    public function shouldFitBounds(): bool
    {
        return (bool) $this->evaluate($this->shouldFitBounds);
    }

    /**
     * @return array{type: 'FeatureCollection', features: list<array<string, mixed>>}|null
     */
    public function getZones(): ?array
    {
        return ZoneState::toDisplayArray($this->getState());
    }

    /**
     * Zones grouped like the widget payload, so both share one Alpine component.
     *
     * @return list<array{key: string, label: string|null, url: string|null, zones: array<string, mixed>}>
     */
    public function getZoneGroups(): array
    {
        $zones = $this->getZones();

        return $zones === null ? [] : [['key' => 'entry', 'label' => null, 'url' => null, 'zones' => $zones]];
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewerConfig(): array
    {
        return [...$this->getBaseMapConfig(), 'fitBounds' => $this->shouldFitBounds()];
    }

    /**
     * @return array<string, string>
     */
    public function getJsLabels(): array
    {
        return JsLabels::viewer();
    }
}
