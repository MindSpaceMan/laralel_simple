<?php

namespace App\DTO;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WeatherRequestDTO
{
    public float $latitude;
    public float $longitude;
    public string $date;

    /**
     * WeatherRequestDTO constructor.
     *
     * @param array $data
     * @throws ValidationException, если данные не прошли валидацию
     */
    public function __construct(array $data)
    {
        $validator = Validator::make($data, [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $this->latitude  = (float) $validated['latitude'];
        $this->longitude = (float) $validated['longitude'];
        $this->date      = $validated['date'];
    }
}
