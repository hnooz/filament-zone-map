<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Concerns;

use Closure;
use Hnooz\FilamentZoneMap\Support\MapSettings;

/**
 * Map settings for schema components (form field, infolist entry).
 * Every getter falls back to config/filament-zone-map.php.
 */
trait HasMapConfiguration
{
    use HasMapStyling;

    protected float|Closure|null $centerLat = null;

    protected float|Closure|null $centerLng = null;

    protected int|Closure|null $zoom = null;

    protected string|Closure|null $height = null;

    protected string|Closure|null $zoneColor = null;

    public function center(float|Closure $lat, float|Closure $lng): static
    {
        $this->centerLat = $lat;
        $this->centerLng = $lng;

        return $this;
    }

    public function zoom(int|Closure $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function height(string|Closure $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function zoneColor(string|Closure $color): static
    {
        $this->zoneColor = $color;

        return $this;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function getCenter(): array
    {
        return MapSettings::center($this->evaluate($this->centerLat), $this->evaluate($this->centerLng));
    }

    public function getZoom(): int
    {
        return MapSettings::zoom($this->evaluate($this->zoom));
    }

    public function getHeight(): string
    {
        return MapSettings::height($this->evaluate($this->height));
    }

    public function getZoneColor(): string
    {
        return MapSettings::color($this->evaluate($this->zoneColor));
    }

    /**
     * Settings every map component sends to the browser.
     *
     * @return array<string, mixed>
     */
    public function getBaseMapConfig(): array
    {
        return [
            ...MapSettings::base(),
            'center' => $this->getCenter(),
            'zoom' => $this->getZoom(),
            'mapId' => $this->getMapId(),
            'styles' => $this->getMapStyles(),
            'zoneColor' => $this->getZoneColor(),
        ];
    }
}
