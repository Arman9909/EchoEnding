<?php

namespace App\Services;
use App\Services\TelegramBotService;
use Illuminate\Support\ServiceProvider;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TelegramBotService::class, function () {
            return new TelegramBotService();
        });
    }
}
