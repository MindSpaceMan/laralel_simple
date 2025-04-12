<?php

namespace App\Services;

use App\Models\WeatherRequest;

class WeatherRequestLogger
{
    /**
     * Сохраняет запись запроса в базу данных.
     *
     * @param float  $latitude
     * @param float  $longitude
     * @param string $date
     * @param string $cityName
     * @param array  $weatherData
     * @return WeatherRequest
     */
    public function log(float $latitude, float $longitude, string $date, string $cityName, array $weatherData): WeatherRequest
    {
        return WeatherRequest::create([
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'request_date' => $date,
            'city_name'    => $cityName,
            'weather_data' => $weatherData,
        ]);
    }
}
