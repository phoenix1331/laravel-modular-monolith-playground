<?php

declare(strict_types=1);

namespace App\Providers;

use App\Capabilities\MembershipStatus;
use App\Capabilities\NullMembershipStatus;
use App\Tenancy\CurrentOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One instance per request or queued job, so an organisation set for one job never leaks into the next
        $this->app->scoped(CurrentOrganisation::class);

        // Capability defaults. A module providing the capability rebinds it in
        // its own provider, which Core registers after this one
        $this->app->bind(MembershipStatus::class, NullMembershipStatus::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Replace the default "php artisan serve" process so php artisan dev serves the app through FrankenPHP
        DevCommands::register('frankenphp php-server --listen :8000 --root public', 'server');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
