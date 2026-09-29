<?php

namespace App\Services;

use App\Exceptions\WeatherUnavailable;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class OpenMeteoService
{
    public function current(): array
    {
        try {
            $response = Http::baseUrl(config('weather.base_url'))
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(config('weather.timeout'))
                ->retry(2, 200, function (Exception $exception, PendingRequest $request): bool {
                    return $exception instanceof ConnectionException || ($exception instanceof RequestException && $exception->response->serverError());
                })
                ->get('/v1/forecast', [
                    'latitude' => config('weather.latitude'),
                    'longitude' => config('weather.longitude'),
                    'timezone' => config('weather.timezone'), 
                    'temperature_unit' => 'celsius', 
                    'wind_speed_unit' => 'kmh',
                    'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                ])->throw();

        } catch (ConnectionException|RequestException $exception) {
            throw new WeatherUnavailable('Weather provider request failed.', previous: $exception);
        }

        $payload = $response->json();

        if (! is_array($payload) || Validator::make($payload, [
            'current.time' => ['required', 'date_format:Y-m-d\TH:i'],
            'current.temperature_2m' => ['required', 'numeric'],
            'current.relative_humidity_2m' => ['required', 'numeric', 'between:0,100'],
            'current.weather_code' => ['required', 'integer', 'between:0,99'],
            'current.wind_speed_10m' => ['required', 'numeric', 'min:0'],
        ])->fails()) {
            throw new WeatherUnavailable('Weather provider returned invalid data.');
        }

        $current = $payload['current'];
        $observed = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $current['time'], config('weather.timezone'));

        if ($observed->lt(now()->subHours(2)) || $observed->gt(now()->addMinutes(15))) {
            throw new WeatherUnavailable('Weather provider returned outdated data.');
        }

        return [
            'location' => config('weather.location'), 
            'timezone' => config('weather.timezone'),
            'temperature' => ['value' => (float) $current['temperature_2m'],'unit' => '°C'],
            'humidity' => ['value' => (float) $current['relative_humidity_2m'], 'unit' => '%'],
            'weather_code' => (int) $current['weather_code'],
            'wind_speed' => ['value' => (float) $current['wind_speed_10m'], 'unit' => 'km/h'],
            'observed_at' => $observed->toIso8601String(), 
            'retrieved_at' => now()->toIso8601String(),
            'source' => 'Open-Meteo',
        ];
    }
}
