<?php

namespace App\Http\Controllers;

use App\DTO\WeatherRequestDTO;
use App\Models\WeatherRequest;
use App\Services\WeatherRequestLogger;
use Illuminate\Http\Request;
use App\Services\YandexWeatherService;
use App\Services\TwoGisGeocoderService;
use Illuminate\Validation\ValidationException;

class WeatherController extends Controller
{
    protected $weatherService;
    protected $geocoderService;
    protected $loggerService;

    public function __construct(
        YandexWeatherService $weatherService,
        TwoGisGeocoderService $geocoderService,
        WeatherRequestLogger $loggerService
    ) {
        $this->weatherService  = $weatherService;
        $this->geocoderService = $geocoderService;
        $this->loggerService   = $loggerService;
    }

    /**
     * Принимает POST-запрос с координатами и датой, возвращает данные о погоде и городе
     *
     * Пример входящего JSON:
     * {
     *   "latitude": 55.7558,
     *   "longitude": 37.6173,
     *   "date": "2025-04-12"
     * }
     */
    public function getWeather(WeatherRequestDTO $weatherDTO)
    {
        $latitude  = $weatherDTO->latitude;
        $longitude = $weatherDTO->longitude;
        $date      = $weatherDTO->date;

        // Получаем название города через 2GIS геокодер
        $cityName = $this->geocoderService->getCityName($latitude, $longitude) ?? 'Неизвестный город';

        // Получаем данные о погоде, включая "weather" и сводку "weather2"
        $formattedWeather = $this->weatherService->getFormattedWeather($latitude, $longitude, $date);
        $weatherData = $formattedWeather['weather']; // Используем для логирования, если нужно
        $weatherSummary = $formattedWeather['weather2'];

        // Сохраняем запрос в базе через сервис логирования (сохраняем по ключу weather)
        $this->loggerService->log($latitude, $longitude, $date, $cityName, $weatherData);

        // Возвращаем ответ с дополнительной сводкой в поле weather2
        return response()->json([
            'city'     => $cityName,
            'weather'  => $weatherData,
            'weather2' => $weatherSummary,
        ]);
    }
}
