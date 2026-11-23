<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Concerns;

use Closure;
use Hnooz\FilamentZoneMap\Support\MapSettings;

/**
 * Side panel to name the selected zone and pick its color.
 */
trait HasZoneEditor
{
    protected bool|Closure $hasEditableZones = false;

    /** @var list<string>|Closure|null */
    protected array|Closure|null $colors = null;

    public function editableZones(bool|Closure $condition = true): static
    {
        $this->hasEditableZones = $condition;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $colors  #RRGGBB palette
     */
    public function colors(array|Closure $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function hasEditableZones(): bool
    {
        return (bool) $this->evaluate($this->hasEditableZones);
    }

    /**
     * Valid #RRGGBB values only, upper-cased. Invalid entries are dropped.
     *
     * @return list<string>
     */
    public function getColors(): array
    {
        $colors = $this->evaluate($this->colors) ?? config('filament-zone-map.colors', []);

        $valid = array_filter(
            is_array($colors) ? $colors : [],
            MapSettings::isColor(...),
        );

        /** @var list<string> $valid */
        $valid = array_values($valid);

        return array_values(array_unique(array_map(strtoupper(...), $valid)));
    }
}
