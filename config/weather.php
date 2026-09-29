<?php

return [
    'base_url' => env('WEATHER_BASE_URL', 'https://api.open-meteo.com'),
    'location' => env('WEATHER_LOCATION', 'Perth, Australia'),
    'latitude' => (float) env('WEATHER_LATITUDE', -31.9523),
    'longitude' => (float) env('WEATHER_LONGITUDE', 115.8613),
    'timezone' => env('WEATHER_TIMEZONE', 'Australia/Perth'),
    'cache_ttl' => (int) env('WEATHER_CACHE_TTL', 900),
    'timeout' => (int) env('WEATHER_TIMEOUT', 5),
];
