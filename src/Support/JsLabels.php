<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Support;

/**
 * Translated strings handed to the Alpine components.
 */
final class JsLabels
{
    /**
     * @return array<string, string>
     */
    public static function field(): array
    {
        return [
            'maxZones' => self::get('messages.max_zones'),
            'maxVertices' => self::get('messages.max_vertices'),
            ...self::common(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function viewer(): array
    {
        return [
            'untitled' => self::get('editor.name_placeholder'),
            'open' => self::get('viewer.open'),
            ...self::common(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function common(): array
    {
        return [
            'loadFailed' => self::get('errors.load_failed'),
            'missingKey' => self::get('errors.missing_key'),
        ];
    }

    private static function get(string $key): string
    {
        $value = __("filament-zone-map::zone-map.{$key}");

        return is_string($value) ? $value : $key;
    }
}
