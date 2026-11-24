<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Hnooz\FilamentZoneMap\Concerns\HasMapConfiguration;
use Hnooz\FilamentZoneMap\Concerns\HasZoneEditor;
use Hnooz\FilamentZoneMap\Concerns\HasZoneLimits;
use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Enums\ZoneShape;
use Hnooz\FilamentZoneMap\Exceptions\InvalidZoneData;
use Hnooz\FilamentZoneMap\Rules\ValidZoneCollection;
use Hnooz\FilamentZoneMap\Support\JsLabels;

/**
 * Draw and edit zones on Google Maps.
 * State: GeoJSON FeatureCollection array, or null when there are no zones.
 */
final class ZoneMap extends Field
{
    use HasMapConfiguration;
    use HasZoneEditor;
    use HasZoneLimits;

    protected string $view = 'filament-zone-map::forms.zone-map';

    /** @var list<string>|Closure|null */
    protected array|Closure|null $shapes = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(static function (ZoneMap $component, mixed $state): void {
            $component->state(self::normalizeState($state));
        });

        $this->dehydrateStateUsing(static fn (mixed $state): ?array => self::normalizeState($state));

        $this->rule(static fn (ZoneMap $component): ValidZoneCollection => new ValidZoneCollection(
            $component->getZoneLimits(),
            required: $component->isRequired(),
        ));
    }

    /**
     * Shapes users can draw (default: polygon, rectangle, circle; add `freehand` to opt in).
     * Stored zones of other shapes still render and stay editable.
     *
     * @param  list<string|ZoneShape>|Closure  $shapes
     */
    public function shapes(array|Closure $shapes): static
    {
        $this->shapes = $shapes instanceof Closure
            ? $shapes
            : array_map(static fn (string|ZoneShape $shape): string => $shape instanceof ZoneShape ? $shape->value : $shape, $shapes);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getShapes(): array
    {
        $shapes = $this->evaluate($this->shapes) ?? ZoneShape::defaults();
        $values = array_map(
            static fn (mixed $shape): mixed => $shape instanceof ZoneShape ? $shape->value : $shape,
            is_array($shapes) ? $shapes : [],
        );

        return array_values(array_intersect(ZoneShape::values(), $values));
    }

    /**
     * Everything the Alpine component needs. Rendered through Js::from (escaped).
     *
     * @return array<string, mixed>
     */
    public function getMapConfig(): array
    {
        return [
            ...$this->getBaseMapConfig(),
            'shapes' => $this->getShapes(),
            'readOnly' => $this->isDisabled(),
            'maxZones' => $this->getMaxZones(),
            'maxVertices' => $this->getMaxVertices(),
            'maxNameLength' => $this->getZoneLimits()->maxNameLength,
            'editableZones' => $this->hasEditableZones(),
            'colors' => $this->getColors(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getJsLabels(): array
    {
        return JsLabels::field();
    }

    /**
     * ZoneCollection (from the cast), array or JSON -> normalised FeatureCollection; empty -> null.
     * Invalid data is passed through unchanged so validation can report it.
     *
     * @return array<array-key, mixed>|null
     */
    private static function normalizeState(mixed $state): ?array
    {
        if (is_string($state)) {
            $state = json_decode($state, true);
        }

        if (! $state instanceof ZoneCollection && ! is_array($state)) {
            return null;
        }

        try {
            $zones = $state instanceof ZoneCollection ? $state : ZoneCollection::fromArray($state);
        } catch (InvalidZoneData) {
            return $state;
        }

        return $zones->isEmpty() ? null : $zones->toArray();
    }
}
