<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Concerns;

use Closure;
use Hnooz\FilamentZoneMap\Support\ZoneLimits;

trait HasZoneLimits
{
    protected bool|Closure $isMultiple = true;

    protected int|Closure|null $maxZones = null;

    protected int|Closure|null $maxVertices = null;

    /** Allow more than one zone. `multiple(false)` limits the field to a single zone. */
    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function maxZones(int|Closure|null $max): static
    {
        $this->maxZones = $max;

        return $this;
    }

    public function maxVertices(int|Closure|null $max): static
    {
        $this->maxVertices = $max;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function getMaxZones(): ?int
    {
        return $this->isMultiple() ? $this->positiveIntOrConfig($this->maxZones, 'max_zones') : 1;
    }

    public function getMaxVertices(): ?int
    {
        return $this->positiveIntOrConfig($this->maxVertices, 'max_vertices');
    }

    public function getZoneLimits(): ZoneLimits
    {
        return new ZoneLimits(
            maxZones: $this->getMaxZones(),
            maxVertices: $this->getMaxVertices(),
            maxNameLength: ZoneLimits::fromConfig()->maxNameLength,
        );
    }

    private function positiveIntOrConfig(int|Closure|null $value, string $configKey): ?int
    {
        $resolved = $this->evaluate($value) ?? config("filament-zone-map.limits.{$configKey}");

        return is_numeric($resolved) && (int) $resolved > 0 ? (int) $resolved : null;
    }
}
