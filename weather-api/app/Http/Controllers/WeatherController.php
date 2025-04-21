<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    private $openWeatherMapApiKey;
    private $client;

    public function __construct()
    {
        $this->openWeatherMapApiKey = env('OPENWEATHERMAP_API_KEY');
        $this->client = new Client([
            'base_uri' => 'https://api.openweathermap.org/',
            'timeout' => 5.0,
        ]);
    }

    /**
     * Get current weather data for a city
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentWeather(Request $request)
    {
        try {
            $city = $request->query('city');
            $units = $request->query('units', 'metric');

            $response = $this->client->get('data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'units' => $units,
                    'appid' => $this->openWeatherMapApiKey,
                ]
            ]);

            return response()->json(json_decode($response->getBody()->getContents()));

        } catch (\Exception $e) {
            Log::error("Weather API Error: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch weather data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weather forecast for next days
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForecast(Request $request)
    {
        try {
            $city = $request->query('city');
            $units = $request->query('units', 'metric');
            $days = $request->query('days', 3);

            $response = $this->client->get('data/2.5/forecast', [
                'query' => [
                    'q' => $city,
                    'units' => $units,
                    'cnt' => $days * 8, // 3-hour intervals
                    'appid' => $this->openWeatherMapApiKey,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents());
            return response()->json($this->processForecastData($data, $days));

        } catch (\Exception $e) {
            Log::error("Forecast API Error: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch forecast data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process raw forecast data into daily summaries
     *
     * @param object $data
     * @param int $days
     * @return array
     */
    private function processForecastData($data, $days)
    {
        $processed = [];
        $dailyData = [];

        // Group by date
        foreach ($data->list as $item) {
            $date = date('Y-m-d', $item->dt);
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [];
            }
            $dailyData[$date][] = $item;
        }

        // Get first $days days
        $dates = array_keys($dailyData);
        $dates = array_slice($dates, 0, $days);

        foreach ($dates as $date) {
            $dayData = $dailyData[$date];
            $processed[] = [
                'date' => $date,
                'temp_min' => min(array_column($dayData, 'main.temp_min')),
                'temp_max' => max(array_column($dayData, 'main.temp_max')),
                'icon' => $dayData[0]->weather[0]->icon,
                'description' => $dayData[0]->weather[0]->description,
            ];
        }

        return $processed;
    }
}
