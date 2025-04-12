<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TwoGisGeocoderService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('DGIS_API_KEY');
        $this->baseUrl = 'https://catalog.api.2gis.com/3.0/items/geocode';
    }

    /**
     * Получает название города по координатам с использованием кэширования.
     *
     * @param float $latitude
     * @param float $longitude
     * @return string
     */
    public function getCityName(float $latitude, float $longitude): string
    {
        // Формируем ключ кэша, зависящий от координат
        $cacheKey = "2gis_geocode_{$latitude}_{$longitude}";

        // Используем Cache::remember для сохранения результата на 24 часа
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($latitude, $longitude) {
            // Формируем параметры запроса к API
            $params = [
                'lat'    => $latitude,
                'lon'    => $longitude,
                'key'    => $this->apiKey,
                'fields' => 'items.point'
            ];

            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['result']['items']) && is_array($data['result']['items'])) {
                    // Ищем элемент с типом 'city'
                    foreach ($data['result']['items'] as $item) {
                        if (isset($item['subtype']) && $item['subtype'] === 'city') {
                            return $item['name'] ?? $item['full_name'] ?? 'Неизвестный город';
                        }
                    }
                    // Если нет элемента с subtype 'city', возвращаем первый элемент
                    $firstItem = $data['result']['items'][0];
                    return $firstItem['name'] ?? $firstItem['full_name'] ?? 'Неизвестный город';
                } else {
                    throw new \Exception("Ответ API не содержит ожидаемых элементов: " . json_encode($data));
                }
            } else {
                throw new \Exception("Ошибка при запросе к 2GIS API. Код ошибки: " . $response->status());
            }
        });
    }
}
