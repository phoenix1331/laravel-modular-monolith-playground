<?php

declare(strict_types=1);

namespace Modules\Subscriptions;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Subscriptions\Console\CheckOverdueFees;
use Modules\Subscriptions\Payments\FakePaymentGateway;
use Modules\Subscriptions\Payments\PaymentGateway;

/**
 * Besides routes and migrations (from the base class), a module provider can
 * own console commands and their schedule. Both disappear when the module is
 * uninstalled, because Core only registers this provider while it is listed
 * in config/modules.php.
 */
final class SubscriptionsServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new SubscriptionsModule;
    }

    public function register(): void
    {
        // The only gateway in this playground. A real one (Stripe, GoCardless)
        // would implement the same interface and be bound here instead
        $this->app->singleton(PaymentGateway::class, FakePaymentGateway::class);
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([CheckOverdueFees::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(CheckOverdueFees::class)->dailyAt('08:00');
        });
    }
}
