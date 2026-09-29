<?php

return [
    'tiles' => [
        'url' => env(
            'MAP_TILE_URL',
            'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        ),

        'attribution' => env(
            'MAP_TILE_ATTRIBUTION',
            '&copy; OpenStreetMap contributors',
        ),

        'max_zoom' => (int) env(
            'MAP_TILE_MAX_ZOOM',
            19,
        ),
    ],

    'center' => [
        'latitude' => (float) env(
            'MAP_CENTER_LAT',
            25.5779,
        ),

        'longitude' => (float) env(
            'MAP_CENTER_LNG',
            91.8837,
        ),
    ],

    'zoom' => (int) env(
        'MAP_ZOOM',
        14,
    ),
];