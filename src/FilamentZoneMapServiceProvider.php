<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class FilamentZoneMapServiceProvider extends PackageServiceProvider
{
    public const string PACKAGE_NAME = 'filament-zone-map';

    public const string ASSET_PACKAGE = 'hnooz/filament-zone-map';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::PACKAGE_NAME)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            assets: [
                AlpineComponent::make('zone-map', __DIR__.'/../resources/dist/zone-map.js'),
                AlpineComponent::make('zone-viewer', __DIR__.'/../resources/dist/zone-viewer.js'),
                Css::make('zone-map', __DIR__.'/../resources/css/zone-map.css')->loadedOnRequest(),
            ],
            package: self::ASSET_PACKAGE,
        );
    }
}
