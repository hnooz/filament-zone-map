<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Concerns;

use Closure;
use Hnooz\FilamentZoneMap\Support\MapSettings;

/**
 * Cloud styling via mapId (preferred) or JSON styles with an optional dark variant.
 * Google ignores `styles` when a mapId is set.
 */
trait HasMapStyling
{
    protected string|Closure|null $mapId = null;

    /** @var array<int, mixed>|Closure|null */
    protected array|Closure|null $lightStyles = null;

    /** @var array<int, mixed>|Closure|null */
    protected array|Closure|null $darkStyles = null;

    public function mapId(string|Closure|null $mapId): static
    {
        $this->mapId = $mapId;

        return $this;
    }

    /**
     * @param  array<int, mixed>|Closure|null  $light  Google Maps JSON style array
     * @param  array<int, mixed>|Closure|null  $dark  Used when Filament is in dark mode
     */
    public function styles(array|Closure|null $light, array|Closure|null $dark = null): static
    {
        $this->lightStyles = $light;
        $this->darkStyles = $dark;

        return $this;
    }

    public function getMapId(): ?string
    {
        return MapSettings::mapId($this->evaluate($this->mapId));
    }

    /**
     * @return array{light: array<int, mixed>|null, dark: array<int, mixed>|null}
     */
    public function getMapStyles(): array
    {
        return [
            'light' => MapSettings::styles($this->evaluate($this->lightStyles), 'light'),
            'dark' => MapSettings::styles($this->evaluate($this->darkStyles), 'dark'),
        ];
    }
}
