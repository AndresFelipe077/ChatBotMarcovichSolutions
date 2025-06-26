<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoService
{
    public function getWeather(string $city, string $date = 'today'): ?array
    {
        try {
            $coordinates = $this->getCoordinates($city);
            if (!$coordinates) {
                return null;
            }

            $isTomorrow = strtolower($date) === 'mañana' || $date === 'tomorrow';
            $forecastDays = $isTomorrow ? 2 : 1;
            
            $res = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude'  => $coordinates['lat'],
                'longitude' => $coordinates['lon'],
                'daily'     => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum',
                'timezone'  => 'auto',
                'forecast_days' => $forecastDays,
                'current_weather' => !$isTomorrow
            ]);

            if (!$res->ok()) {
                Log::error('OpenMeteo API Error', ['response' => $res->body()]);
                return null;
            }

            $data = $res->json();
            
            if ($isTomorrow) {
                return [
                    'city' => $city,
                    'temperature' => $data['daily']['temperature_2m_max'][1] ?? null,
                    'weathercode' => $data['daily']['weathercode'][1] ?? 0,
                    'precipitation' => $data['daily']['precipitation_sum'][1] ?? 0,
                    'is_tomorrow' => true
                ];
            }

            
            return [
                'city' => $city,
                'temperature' => $data['current_weather']['temperature'] ?? null,
                'weathercode' => $data['current_weather']['weathercode'] ?? 0,
                'precipitation' => $data['daily']['precipitation_sum'][0] ?? 0,
                'is_tomorrow' => false
            ];
        } catch (\Exception $e) {
            Log::error('Error getting weather data', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getCoordinates(string $city): ?array
    {
        try {
            $res = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get("https://nominatim.openstreetmap.org/search", [
                'q' => $city,
                'format' => 'json',
                'limit' => 1,
                'accept-language' => 'es'
            ]);

            if ($res->ok() && !empty($res->json())) {
                $data = $res->json()[0];
                return [
                    'lat' => (float)$data['lat'],
                    'lon' => (float)$data['lon']
                ];
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting coordinates', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function interpretWeatherCode(int $code, bool $isRaining = false): string
    {
        $weatherMap = [
            // Clear
            0 => 'Despejado',
            // Mainly clear, partly cloudy, and overcast
            1 => 'Mayormente despejado',
            2 => 'Parcialmente nublado',
            3 => 'Nublado',
            // Fog and depositing rime fog
            45 => 'Niebla',
            48 => 'Niebla con escarcha',
            // Drizzle
            51 => 'Llovizna ligera',
            53 => 'Llovizna moderada',
            55 => 'Llovizna densa',
            // Freezing Drizzle
            56 => 'Llovizna helada ligera',
            57 => 'Llovizna helada densa',
            // Rain
            61 => 'Lluvia ligera',
            63 => 'Lluvia moderada',
            65 => 'Lluvia fuerte',
            // Freezing Rain
            66 => 'Lluvia helada ligera',
            67 => 'Lluvia helada fuerte',
            // Snow fall
            71 => 'Nieve ligera',
            73 => 'Nieve moderada',
            75 => 'Nieve fuerte',
            // Snow grains
            77 => 'Granos de nieve',
            // Rain showers
            80 => 'Chubascos ligeros',
            81 => 'Chubascos moderados',
            82 => 'Chubascos fuertes',
            // Snow showers
            85 => 'Chubascos de nieve ligeros',
            86 => 'Chubascos de nieve fuertes',
            // Thunderstorm
            95 => 'Tormenta eléctrica',
            96 => 'Tormenta con granizo ligero',
            99 => 'Tormenta con granizo fuerte'
        ];

        return $weatherMap[$code] ?? 'Condiciones desconocidas';
    }
}
