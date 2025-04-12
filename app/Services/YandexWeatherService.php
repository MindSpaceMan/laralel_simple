<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class YandexWeatherService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('YANDEX_WEATHER_API_KEY');
        // Проверьте актуальный URL в документации Яндекс Weather API
        $this->baseUrl = 'https://api.weather.yandex.ru/v2/forecast';
    }

    /**
     * Получает данные о погоде от Яндекс Weather API по координатам и дате.
     * Здесь добавлено кэширование для снижения количества запросов к API.
     *
     * @param float  $latitude
     * @param float  $longitude
     * @param string $date
     * @return array|null
     */
    public function getWeather(float $latitude, float $longitude, string $date): ?array
    {
        // Формируем уникальный ключ для кэша.
        // Включаем в ключ координаты и дату, так как прогноз может меняться с течением времени.
        $cacheKey = "yandex_weather_{$latitude}_{$longitude}_{$date}";

        // Пытаемся получить данные из кэша, если их нет – выполняем запрос к API и кэшируем результат на 30 минут.
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($latitude, $longitude, $date) {
            $params = [
                'lat' => $latitude,
                'lon' => $longitude,
                // При необходимости можно добавить дополнительные параметры, например 'lang', 'limit' и т.д.
            ];

            $response = Http::withHeaders([
                'X-Yandex-API-Key' => $this->apiKey,
            ])->get($this->baseUrl, $params);

            if ($response->successful()) {
                return $response->json();
            }

            // Если запрос не успешен, можно вернуть null или выбросить исключение.
            return null;
        });
    }

    /**
     * Получает данные о погоде и формирует сводку.
     *
     * Возвращает массив с двумя ключами:
     *   - 'weather'  => исходные данные от API.
     *   - 'weather2' => сводная информация (например, текущее время, температура и состояние).
     *
     * @param float  $latitude
     * @param float  $longitude
     * @param string $date
     * @return array
     */
    public function getFormattedWeather(float $latitude, float $longitude, string $date): array
    {
        $data = $this->getWeather($latitude, $longitude, $date);

        // Если данные не получены, возвращаем значения по умолчанию.
        if (is_null($data)) {
            return [
                'weather'  => null,
                'weather2' => [
                    'current_time' => null,
                    'temperature'  => null,
                    'condition'    => 'Данные погоды не получены',
                ],
            ];
        }

        // Формируем сводку – берем нужные поля (если они существуют в ответе).
        $weatherSummary = [
            'current_time' => $data['now_dt'] ?? null,
            'temperature'  => $data['fact']['temp'] ?? null,
            'condition'    => $data['fact']['condition'] ?? null,
        ];

        return [
            'weather'  => $data,
            'weather2' => $weatherSummary,
        ];
    }
}
