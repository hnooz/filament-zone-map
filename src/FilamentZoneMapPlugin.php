<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class FilamentZoneMapPlugin implements Plugin
{
    public const string ID = 'filament-zone-map';

    public static function make(): static
    {
        return app(self::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(self::ID);

        return $plugin;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        // Widgets are registered by the user on their own dashboards.
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
