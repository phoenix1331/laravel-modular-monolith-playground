<?php

declare(strict_types=1);

namespace App\Audit;

use App\Models\AuditLog;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

/**
 * Writes one audit entry. Called by AuditObserver for model events, by the
 * auth listeners for logins, and directly by any code with something worth
 * recording that is not a model change (e.g. toggling a module).
 *
 * The request and user are read at the moment of logging rather than
 * captured when the service is built, so a long-lived instance in a queue
 * worker never records a stale request.
 */
class AuditLogger
{
    public function __construct(
        private readonly Container $container,
        private readonly AuthFactory $auth,
        private readonly CurrentOrganisation $currentOrganisation,
    ) {}

    /**
     * @param  array<string, mixed>  $eventData
     */
    public function log(
        AuditAction $action,
        string $subjectType,
        int|string|null $subjectId = null,
        array $eventData = [],
        ?int $organisationId = null,
    ): AuditLog {
        $request = $this->container->bound('request') ? $this->container->make(Request::class) : null;
        $userId = $this->auth->guard()->id();

        return AuditLog::create([
            'organisation_id' => $organisationId ?? $this->currentOrganisation->id(),
            'user_id' => is_int($userId) ? $userId : null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'event_data' => $eventData,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_route' => $request?->route()?->getName(),
        ]);
    }
}
