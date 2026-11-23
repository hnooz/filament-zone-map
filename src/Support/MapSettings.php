<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Support;

/**
 * Sanitises map settings and builds the config sent to the browser.
 * Shared by the form field, infolist entry and widget. Null = use config file.
 */
final class MapSettings
{
    public const string COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    private const string HEIGHT_PATTERN = '/^\d+(\.\d+)?(px|rem|em|vh|svh|dvh|%)$/';

    private const string DEFAULT_COLOR = '#E74C3C';

    private const string DEFAULT_HEIGHT = '480px';

    /**
     * @param  array{center?: array{0: mixed, 1: mixed}|null, zoom?: mixed, mapId?: mixed, styles?: array{light?: mixed, dark?: mixed}, zoneColor?: mixed}  $overrides
     * @return array<string, mixed>
     */
    public static function base(array $overrides = []): array
    {
        $center = $overrides['center'] ?? null;

        return [
            'key' => self::string(config('filament-zone-map.browser_key')),
            'language' => self::string(config('filament-zone-map.language')) ?? app()->getLocale(),
            'region' => self::string(config('filament-zone-map.region')),
            'center' => self::center($center[0] ?? null, $center[1] ?? null),
            'zoom' => self::zoom($overrides['zoom'] ?? null),
            'mapId' => self::mapId($overrides['mapId'] ?? null),
            'styles' => [
                'light' => self::styles($overrides['styles']['light'] ?? null, 'light'),
                'dark' => self::styles($overrides['styles']['dark'] ?? null, 'dark'),
            ],
            'zoneColor' => self::color($overrides['zoneColor'] ?? null),
        ];
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public static function center(mixed $lat, mixed $lng): array
    {
        $lat ??= config('filament-zone-map.defaults.center.lat', 0);
        $lng ??= config('filament-zone-map.defaults.center.lng', 0);

        return [
            'lat' => max(-90.0, min(90.0, is_numeric($lat) ? (float) $lat : 0.0)),
            'lng' => max(-180.0, min(180.0, is_numeric($lng) ? (float) $lng : 0.0)),
        ];
    }

    public static function zoom(mixed $zoom): int
    {
        $zoom ??= config('filament-zone-map.defaults.zoom', 2);

        return max(0, min(22, is_numeric($zoom) ? (int) $zoom : 2));
    }

    /** Only safe CSS lengths; anything else falls back to the default. */
    public static function height(mixed $height): string
    {
        $height ??= config('filament-zone-map.defaults.height');

        return is_string($height) && preg_match(self::HEIGHT_PATTERN, $height) === 1 ? $height : self::DEFAULT_HEIGHT;
    }

    public static function color(mixed $color): string
    {
        $color ??= config('filament-zone-map.zone_color');

        return self::isColor($color) ? strtoupper((string) $color) : self::DEFAULT_COLOR;
    }

    public static function isColor(mixed $color): bool
    {
        return is_string($color) && preg_match(self::COLOR_PATTERN, $color) === 1;
    }

    public static function mapId(mixed $mapId): ?string
    {
        return self::string($mapId ?? config('filament-zone-map.map_id'));
    }

    /**
     * @return array<int, mixed>|null
     */
    public static function styles(mixed $styles, string $variant): ?array
    {
        $styles ??= config("filament-zone-map.styles.{$variant}");

        return is_array($styles) && $styles !== [] ? array_values($styles) : null;
    }

    /** Relative paths and http(s) URLs only (no javascript:, data:, //host). */
    public static function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $isRelative = str_starts_with($url, '/') && ! str_starts_with($url, '//');

        return $isRelative || preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
