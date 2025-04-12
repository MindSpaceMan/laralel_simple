<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherRequest extends Model
{
    use HasFactory;

    protected $table = 'weather_requests';

    protected $fillable = [
        'latitude',
        'longitude',
        'request_date',
        'city_name',
        'weather_data',
    ];

    protected $casts = [
        'weather_data' => 'array', // Автоматическое преобразование JSON в массив
    ];
}
