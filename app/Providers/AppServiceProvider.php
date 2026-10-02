<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Replace the default "php artisan serve" process so php artisan dev serves the app through FrankenPHP
        DevCommands::register('frankenphp php-server --listen :8000 --root public', 'server');
    }
}
