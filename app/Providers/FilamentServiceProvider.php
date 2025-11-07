<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // оставь как есть, если там пусто
    }

    public function boot(): void
    {
        // ✅ Даем доступ к админке всем пользователям
        Gate::define('accessFilament', fn ($user) => true);
    }
}
