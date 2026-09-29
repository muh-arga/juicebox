<?php

namespace App\Jobs;

use App\Services\WeatherService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshWeather implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 3600;

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(WeatherService $weather): void
    {
        $weather->refresh();
    }
}
