<?php

namespace Tests\Feature;

use App\Exceptions\WeatherUnavailable;
use App\Jobs\RefreshWeather;
use App\Models\User;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'weather.base_url' => 'https://api.open-meteo.com',
            'weather.location' => 'Perth, Australia',
            'weather.latitude' => -31.9523,
            'weather.longitude' => 115.8613,
            'weather.timezone' => 'Australia/Perth',
            'weather.cache_ttl' => 900,
        ]);

        $this->travelTo(now()->startOfMinute());
        Http::preventStrayRequests();
        Sanctum::actingAs(User::factory()->create());
    }

    private function payload(float $temperature = 23.5): array
    {
        return [
            'current' => [
                'time' => now(config('weather.timezone'))->format('Y-m-d\TH:i'),
                'temperature_2m' => $temperature,
                'relative_humidity_2m' => 65,
                'weather_code' => 1,
                'wind_speed_10m' => 12
            ]
        ];
    }

    public function test_weather_maps_perth_data_and_reuses_cache(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->payload())
        ]);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location', 'Perth, Australia')
            ->assertJsonPath('data.temperature.value', 23.5)
            ->assertJsonPath('data.wind_speed.unit', 'km/h')
            ->assertJsonStructure([
                'data' => [
                    'observed_at',
                    'retrieved_at'
                ]
            ]);

        $this->getJson('/api/weather')->assertOk();
        Http::assertSentCount(1);

        Http::assertSent(fn ($request) => $request['latitude'] == -31.9523
            && $request['longitude'] == 115.8613
            && $request['timezone'] === 'Australia/Perth'
            && $request['temperature_unit'] === 'celsius');
    }

    public function test_configured_location_coordinates_and_timezone_are_used(): void
    {
        config([
            'weather.location' => 'Jakarta, Indonesia',
            'weather.latitude' => -6.2088,
            'weather.longitude' => 106.8456,
            'weather.timezone' => 'Asia/Jakarta',
        ]);

        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->payload())
        ]);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location', 'Jakarta, Indonesia')
            ->assertJsonPath('data.timezone', 'Asia/Jakarta')
            ->assertJsonPath('data.observed_at', now('Asia/Jakarta')->toIso8601String());

        Http::assertSent(fn ($request) => $request['latitude'] == -6.2088
            && $request['longitude'] == 106.8456
            && $request['timezone'] === 'Asia/Jakarta');
    }

    public function test_changing_location_label_does_not_serve_the_previous_cached_label(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->payload())
        ]);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location', 'Perth, Australia');

        config(['weather.location' => 'Perth, Western Australia']);

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location', 'Perth, Western Australia');

        $this->getJson('/api/weather')
            ->assertOk()
            ->assertJsonPath('data.location', 'Perth, Western Australia');

        Http::assertSentCount(2);
    }

    public function test_cache_expires_after_fifteen_minutes(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->payload())
        ]);

        $this->getJson('/api/weather')->assertOk();
        $this->travel(14)->minutes();

        $this->getJson('/api/weather')->assertOk();
        Http::assertSentCount(1);

        $this->travel(2)->minutes();

        $this->getJson('/api/weather')->assertOk();
        Http::assertSentCount(2);
    }

    public function test_server_failure_is_retried_and_not_cached(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::sequence()
                ->push([], 500)
                ->push([], 500)
                ->push($this->payload())
        ]);

        $this->getJson('/api/weather')
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Weather is temporarily unavailable. Please try again later.'
            ]);

        $this->getJson('/api/weather')->assertOk();
        Http::assertSentCount(3);
    }

    public function test_client_failure_is_not_retried(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([], 400)
        ]);

        $this->getJson('/api/weather')->assertStatus(503);
        Http::assertSentCount(1);
    }

    public function test_connection_failure_returns_service_unavailable(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::failedConnection()
        ]);

        $this->getJson('/api/weather')->assertStatus(503);
    }

    public function test_malformed_or_outdated_data_is_rejected(): void
    {
        $old = $this->payload();
        $old['current']['time'] = now(config('weather.timezone'))->subDay()->format('Y-m-d\TH:i');

        Http::fake([
            'api.open-meteo.com/*' => Http::sequence()
                ->push(['current' => []])
                ->push($old)
                ->push('not json')
                ->push($this->payload())
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/weather')->assertStatus(503);
        }

        $this->getJson('/api/weather')->assertOk();
    }

    public function test_refresh_job_replaces_cached_weather(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::sequence()
                ->push($this->payload(20))
                ->push($this->payload(25))
        ]);

        $this->getJson('/api/weather')->assertJsonPath('data.temperature.value', 20);

        (new RefreshWeather)->handle(app(WeatherService::class));

        $this->getJson('/api/weather')->assertJsonPath('data.temperature.value', 25);
        Http::assertSentCount(2);
    }

    public function test_failed_refresh_preserves_unexpired_cache_but_does_not_extend_it(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::sequence()
                ->push($this->payload())
                ->push([], 500)
                ->push([], 500)
                ->push([], 500)
                ->push([], 500)
        ]);

        $this->getJson('/api/weather')->assertOk();
        $this->travel(14)->minutes();

        try {
            (new RefreshWeather)->handle(app(WeatherService::class));
            $this->fail('Expected provider failure.');
        } catch (WeatherUnavailable) {
        }

        $this->getJson('/api/weather')->assertOk();
        $this->travel(2)->minutes();

        $this->getJson('/api/weather')->assertStatus(503);
    }
}
