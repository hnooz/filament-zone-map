<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Tests\Fixtures;

use Closure;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Hnooz\FilamentZoneMap\Forms\Components\ZoneMap;
use Livewire\Component;

/**
 * Minimal Livewire page hosting a ZoneMap field for feature tests.
 *
 * @property-read Schema $form
 */
final class ZoneFormPage extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** Customises the field per test: fn (ZoneMap $field): ZoneMap */
    public static ?Closure $configureField = null;

    /** Value passed to form->fill() on mount (array, JSON, ZoneCollection or null). */
    public static mixed $initialState = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $saved = null;

    public static function resetFixture(): void
    {
        self::$configureField = null;
        self::$initialState = null;
    }

    public function mount(): void
    {
        $this->form->fill(['coverage' => self::$initialState]);
    }

    public function form(Schema $schema): Schema
    {
        $field = ZoneMap::make('coverage');

        if (self::$configureField instanceof Closure) {
            $field = (self::$configureField)($field);
        }

        return $schema->components([$field])->statePath('data');
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
            BLADE;
    }
}
