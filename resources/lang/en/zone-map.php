<?php

declare(strict_types=1);

return [

    'toolbar' => [
        'label' => 'Drawing tools',
    ],

    'modes' => [
        'polygon' => 'Polygon',
        'rectangle' => 'Rectangle',
        'circle' => 'Circle',
        'select' => 'Select',
    ],

    'actions' => [
        'delete' => 'Delete zone',
        'clear' => 'Clear all',
        'confirm_clear' => 'Click again to clear all zones',
    ],

    'editor' => [
        'title' => 'Selected zone',
        'name' => 'Zone name',
        'name_placeholder' => 'Untitled zone',
        'color' => 'Color',
    ],

    'viewer' => [
        'open' => 'Open',
    ],

    'messages' => [
        'max_zones' => 'You can draw up to :max zones.',
        'max_vertices' => 'A zone can have up to :max points.',
    ],

    'validation' => [
        'invalid' => 'The :attribute must contain valid zones.',
        'max_zones' => 'The :attribute may not have more than :max zones.',
        'max_vertices' => 'Each zone in :attribute may not have more than :max points.',
        'name_too_long' => 'Zone names in :attribute may not be longer than :max characters.',
        'invalid_color' => 'Zone colors in :attribute must be valid hex colors (e.g. #E74C3C).',
    ],

    'errors' => [
        'missing_key' => 'Google Maps browser key is not configured.',
        'load_failed' => 'The map could not be loaded. Check the API key and your connection.',
    ],

];
