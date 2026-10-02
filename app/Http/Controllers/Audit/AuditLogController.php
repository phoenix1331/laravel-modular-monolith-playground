<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Queries\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only audit trail. Serves both the club area (/audit) and the platform
 * area (/platform/audit); AuditTrail decides which entries each user sees.
 */
class AuditLogController extends Controller
{
    public function index(Request $request, AuditTrail $trail): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        /** @var User $user */
        $user = $request->user();

        $entries = $trail->for($user)->paginate(25)->through(fn (AuditLog $entry) => [
            'id' => $entry->id,
            'created_at' => $entry->created_at->toIso8601String(),
            'action' => $entry->action->value,
            'action_label' => $entry->action->label(),
            'subject_type' => $entry->subject_type,
            'subject_id' => $entry->subject_id,
            'subject_label' => $this->subjectLabel($entry),
            'changes' => $entry->event_data['changes'] ?? null,
            'user' => $entry->user?->name,
            'organisation' => $entry->organisation?->name,
            'ip_address' => $entry->ip_address,
        ]);

        return Inertia::render('audit/Index', [
            'entries' => $entries,
            'showOrganisation' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * A readable name for the record, taken from the snapshot stored with the
     * entry so it still works after the record is deleted.
     */
    private function subjectLabel(AuditLog $entry): ?string
    {
        $attributes = $entry->event_data['attributes'] ?? [];

        if (! is_array($attributes)) {
            return null;
        }

        if (isset($attributes['first_name'], $attributes['last_name'])) {
            return "{$attributes['first_name']} {$attributes['last_name']}";
        }

        $label = $attributes['name'] ?? $attributes['email'] ?? null;

        return is_string($label) ? $label : null;
    }
}
