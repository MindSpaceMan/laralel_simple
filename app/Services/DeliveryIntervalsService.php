<?php

namespace App\Services;

use App\DTO\GetIntervalsDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DeliveryIntervalsService
{
    // Константа с праздниками: день и месяц, без учёта года
    private const HOLIDAYS = [
        '01.01', // 01 января любого года
        '08.03', // 08 марта
        '09.05', // 09 мая
    ];

    // Сколько дней нужно набрать в ответе
    private const INTERVALS_COUNT = 21;

    // Для Город_1 и Город_2: понедельник (1), среда (3), пятница (5)
    private const CITY1_2_DAYS = [1, 3, 5];

    // Для Город_3: вторник (2), четверг (4), суббота (6)
    private const CITY3_DAYS = [2, 4, 6];

    /**
     * Генерирует интервалы доставки с учётом всех правил, используя кэш
     *
     * @param GetIntervalsDTO $dto
     * @return array
     */
    public function getIntervals(GetIntervalsDTO $dto): array
    {
        // Формируем ключ кэша на основе даты, времени, направления
        $cacheKey = $this->makeCacheKey($dto);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($dto) {
            return $this->generateIntervals($dto);
        });
    }

    /**
     * Генерация интервалов (без учёта кэша, "чистая" логика).
     *
     * @param GetIntervalsDTO $dto
     * @return array
     */
    private function generateIntervals(GetIntervalsDTO $dto): array
    {
        $direction = $dto->direction;
        // Копируем стартовую дату, чтобы не менять оригинал
        $currentDate = $dto->startDate->copy();

        // Убираем текущий день (по условию) — начинаем со следующего
        $currentDate->addDay();

        // Нужно ли пропускать "следующий день" в зависимости от времени?
        // Для Город_1/Город_2: если время > 16:00 -> пропуск следующего M/W/F.
        // Для Город_3: если время > 22:00 -> пропуск следующего T/Th/Sat.
        $skipNextDaySet = [];
        if (in_array($direction, ['Город_1', 'Город_2'])) {
            // Если время больше 16:00
            if ($this->isTimeGreaterThan($dto->time, '16:00')) {
                $skipNextDaySet = self::CITY1_2_DAYS; // [1,3,5]
            }
        } else {
            // Город_3
            if ($this->isTimeGreaterThan($dto->time, '22:00')) {
                $skipNextDaySet = self::CITY3_DAYS;   // [2,4,6]
            }
        }

        $days = [];
        $countProtector = 0;
        $found = 0;

        while ($found < self::INTERVALS_COUNT) {
            // Если день — праздник, пропускаем
            if ($this->isHoliday($currentDate)) {
                $currentDate->addDay();
                $countProtector++;
                if ($countProtector > 60) {
                    break;
                }
                continue;
            }

            // Определяем нужный набор дней недели для данного направления
            $neededDays = $this->getNeededDays($direction);

            if (in_array($currentDate->dayOfWeekIso, $neededDays)) {
                // Проверка на условие "пропустить следующий день", если нужно
                if (!empty($skipNextDaySet)) {
                    // Если dayOfWeekIso совпадает, пропускаем и сбрасываем флаг
                    if (in_array($currentDate->dayOfWeekIso, $skipNextDaySet)) {
                        $currentDate->addDay();
                        $skipNextDaySet = []; // сбросили, далее обычная логика
                        $countProtector++;
                        if ($countProtector > 60) {
                            break;
                        }
                        continue;
                    }
                }

                // Добавляем день в итоговый массив
                $days[] = [
                    'date'  => $currentDate->format('d.m.Y'),
                    'day'   => $currentDate->translatedFormat('l'),
                    'title' => $currentDate->format('j F'),
                ];
                $found++;
            }

            $currentDate->addDay();
            $countProtector++;
            if ($countProtector > 60) {
                // Предохранитель от бесконечного цикла
                break;
            }
        }

        return $days;
    }

    /**
     * Формирует ключ для кэша на основе даты, времени и направления
     *
     * @param GetIntervalsDTO $dto
     * @return string
     */
    private function makeCacheKey(GetIntervalsDTO $dto): string
    {
        // Например: delivery_intervals_2025-04-12_14:00_Город_1
        // Учитываем, что direction может содержать кириллицу — транслитерация не обязательна,
        // если настроен драйвер кэша (Redis, etc.) поддерживающий Unicode. Но иногда лучше почистить.
        return sprintf(
            'delivery_intervals_%s_%s_%s',
            $dto->startDate->format('Y-m-d'),
            $dto->time,
            $dto->direction
        );
    }

    /**
     * Возвращает true, если строковое время $timeStr > $compareTimeStr.
     *
     * @param string $timeStr       "16:45"
     * @param string $compareTimeStr "16:00"
     * @return bool
     */
    private function isTimeGreaterThan(string $timeStr, string $compareTimeStr): bool
    {
        // Сравниваем часы и минуты
        $time    = Carbon::createFromFormat('H:i', $timeStr);
        $compare = Carbon::createFromFormat('H:i', $compareTimeStr);

        return $time->greaterThan($compare);
    }

    /**
     * Проверяет, является ли день праздничным по константе HOLIDAYS
     *
     * @param Carbon $date
     * @return bool
     */
    private function isHoliday(Carbon $date): bool
    {
        // Формат "дд.мм" без года
        $dm = $date->format('d.m');

        return in_array($dm, self::HOLIDAYS);
    }

    /**
     * Определяет, какие дни недели нужны для переданного направления.
     *
     * @param string $direction
     * @return int[]
     */
    private function getNeededDays(string $direction): array
    {
        if (in_array($direction, ['Город_1', 'Город_2'])) {
            return self::CITY1_2_DAYS; // [1, 3, 5]
        }
        // Иначе Город_3
        return self::CITY3_DAYS; // [2, 4, 6]
    }
}
