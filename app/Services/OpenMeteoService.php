<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenMeteoService
{
    public function getWeather(float $latitude, float $longitude): ?array
    {
        $res = Http::get('https://api.open-meteo.com/v1/forecast', [
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'daily'     => 'temperature_2m_max,temperature_2m_min,precipitation_sum',
            'timezone'  => 'auto'
        ]);

        return $res->ok() ? $res->json() : null;
    }

    public function getCoordinates(string $city): ?array
    {
        $res = Http::get("https://nominatim.openstreetmap.org/search", [
            'q'      => $city,
            'format' => 'json',
            'limit'  => 1,
        ]);

        if ($res->ok() && !empty($res[0])) {
            return [
                'lat' => $res[0]['lat'],
                'lon' => $res[0]['lon'],
            ];
        }

        return null;
    }
}
