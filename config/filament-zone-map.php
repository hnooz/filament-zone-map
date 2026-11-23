<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Google Maps browser key
    |--------------------------------------------------------------------------
    |
    | This key is sent to the browser. Restrict it in Google Cloud Console to
    | your HTTP referrers and to the "Maps JavaScript API" only.
    |
    */

    'browser_key' => env('FILAMENT_ZONE_MAP_BROWSER_KEY', env('GOOGLE_MAPS_API_KEY')),

    'language' => env('FILAMENT_ZONE_MAP_LANGUAGE'),

    'region' => env('FILAMENT_ZONE_MAP_REGION'),

    /*
    |--------------------------------------------------------------------------
    | Map defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'center' => [
            'lat' => (float) env('FILAMENT_ZONE_MAP_CENTER_LAT', 0),
            'lng' => (float) env('FILAMENT_ZONE_MAP_CENTER_LNG', 0),
        ],
        'zoom' => (int) env('FILAMENT_ZONE_MAP_ZOOM', 2),
        'height' => '480px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Styling
    |--------------------------------------------------------------------------
    |
    | "map_id" enables cloud-based map styling and takes priority. Google
    | ignores JSON "styles" when a map ID is set. Without a map ID, the light
    | or dark JSON styles are applied based on the Filament theme.
    |
    */

    'map_id' => env('FILAMENT_ZONE_MAP_MAP_ID'),

    'styles' => [
        'light' => null,
        'dark' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Zones
    |--------------------------------------------------------------------------
    */

    'zone_color' => '#E74C3C',

    'colors' => [
        '#E74C3C', '#E67E22', '#F1C40F', '#2ECC71',
        '#1ABC9C', '#3498DB', '#9B59B6', '#34495E',
    ],

    'limits' => [
        'max_zones' => 10,
        'max_vertices' => 500,
        'max_name_length' => 100,
    ],

];
