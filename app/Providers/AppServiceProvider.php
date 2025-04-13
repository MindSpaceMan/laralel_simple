<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\DTO\GetIntervalsDTO::class, function ($app) {
            // Получаем все данные из текущего запроса
            $data = request()->all();
            // Создаём новый DTO, валидация произойдёт в его конструкторе
            return new \App\DTO\GetIntervalsDTO($data);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Carbon\Carbon::setLocale('ru');
    }
}
