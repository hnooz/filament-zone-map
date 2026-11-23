<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Tests\Fixtures;

use Closure;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Hnooz\FilamentZoneMap\Infolists\Components\ZoneMapEntry;
use Livewire\Component;

/**
 * Livewire page rendering a ZoneMapEntry for an unsaved ZoneRecord.
 */
final class ZoneInfolistPage extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** fn (ZoneMapEntry $entry): ZoneMapEntry */
    public static ?Closure $configureEntry = null;

    /** Raw column value (JSON string or null). */
    public static ?string $coverage = null;

    public static function resetFixture(): void
    {
        self::$configureEntry = null;
        self::$coverage = null;
    }

    public function infolist(Schema $schema): Schema
    {
        $record = new ZoneRecord;
        $record->setRawAttributes(['id' => 1, 'name' => 'North', 'coverage' => self::$coverage]);

        $entry = ZoneMapEntry::make('coverage');

        if (self::$configureEntry instanceof Closure) {
            $entry = (self::$configureEntry)($entry);
        }

        return $schema->record($record)->components([$entry]);
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->infolist }}</div>
            BLADE;
    }
}
