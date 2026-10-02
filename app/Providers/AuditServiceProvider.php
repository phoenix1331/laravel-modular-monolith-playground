<?php

declare(strict_types=1);

namespace App\Providers;

use App\Audit\Listeners\RecordAuthentication;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires up the audit log. Model auditing needs no registration here: each
 * model opts in with `#[ObservedBy(AuditObserver::class)]`, so modules can do
 * the same without this provider ever naming them.
 */
class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Login::class, [RecordAuthentication::class, 'handleLogin']);
        Event::listen(Logout::class, [RecordAuthentication::class, 'handleLogout']);
    }
}
