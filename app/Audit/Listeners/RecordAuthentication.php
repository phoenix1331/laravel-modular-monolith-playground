<?php

declare(strict_types=1);

namespace App\Audit\Listeners;

use App\Audit\AuditAction;
use App\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Records logins and logouts. These are not model changes, so they come from
 * Laravel's auth events rather than the observer.
 */
class RecordAuthentication
{
    public function __construct(private readonly AuditLogger $logger) {}

    public function handleLogin(Login $event): void
    {
        $this->record(AuditAction::Login, $event->user);
    }

    public function handleLogout(Logout $event): void
    {
        $this->record(AuditAction::Logout, $event->user);
    }

    private function record(AuditAction $action, mixed $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        $this->logger->log($action, 'user', $user->id, organisationId: $user->organisation_id);
    }
}
