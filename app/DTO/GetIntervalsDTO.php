<?php

namespace App\DTO;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class GetIntervalsDTO
{
    public Carbon $startDate;
    public string $direction;
    public string $time; // строка вида '16:30', чтобы хранить оригинальное время

    /**
     * @throws ValidationException
     */
    public function __construct(array $data)
    {
        $validator = Validator::make($data, [
            'date'      => 'required|date_format:Y-m-d',
            'time'      => 'required|date_format:H:i',
            'direction' => 'required|in:Город_1,Город_2,Город_3',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated  = $validator->validated();
        $this->time = $validated['time'];
        $this->direction = $validated['direction'];

        // Объединяем дату и время (строчного формата) в объект Carbon
        $this->startDate = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date'].' '.$validated['time']
        );
    }
}
