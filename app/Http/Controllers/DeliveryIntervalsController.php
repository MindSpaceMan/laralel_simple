<?php

namespace App\Http\Controllers;

use App\DTO\GetIntervalsDTO;
use App\Services\DeliveryIntervalsService;
use Illuminate\Http\JsonResponse;

class DeliveryIntervalsController extends Controller
{
    protected DeliveryIntervalsService $deliveryService;

    public function __construct(DeliveryIntervalsService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Обрабатывает GET-запрос, возвращая интервалы доставки в JSON
     *
     * Пример запроса:
     *   GET /api/delivery-intervals?date=2025-04-12&time=17:30&direction=Город_1
     */
    public function getIntervals(GetIntervalsDTO $dto): JsonResponse
    {
        // Сервис сам берёт данные из DTO и возвращает результат (из кэша или сгенерирует заново).
        $intervals = $this->deliveryService->getIntervals($dto);

        return response()->json($intervals);
    }
}
