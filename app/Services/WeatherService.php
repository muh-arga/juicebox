<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class WeatherService
{
    public function __construct(private readonly OpenMeteoService $provider) {}

    public function current(): array
    {
        return Cache::remember($this->cacheKey(), config('weather.cache_ttl'), fn () => $this->provider->current());
    }

    public function refresh(): array
    {
        $weather = $this->provider->current();
        Cache::put($this->cacheKey(), $weather, config('weather.cache_ttl'));

        return $weather;
    }

    private function cacheKey(): string
    {
        return 'weather:'.hash('sha256', json_encode([
            config('weather.base_url'), config('weather.location'), config('weather.latitude'), config('weather.longitude'), config('weather.timezone'),
        ]));
    }
}
