<?php

namespace App\Http\Controllers;

use App\Exceptions\WeatherUnavailable;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    public function show(WeatherService $weather)
    {
        try {
            return response()->json([
                'data' => $weather->current()
            ]);
            
        } catch (WeatherUnavailable $exception) {
            Log::warning('Weather unavailable.', ['reason' => $exception->getMessage()]);

            return response()->json([
                'message' => 'Weather is temporarily unavailable. Please try again later.'
            ], 503);
        }
    }
}
