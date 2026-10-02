<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\AuditAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One entry in the append-only audit trail.
 *
 * Deliberately not BelongsToOrganisation: platform entries have no
 * organisation, and the super admin reads across all of them. Reads filter by
 * organisation explicitly in App\Queries\AuditTrail.
 *
 * @property int $id
 * @property int|null $organisation_id
 * @property int|null $user_id
 * @property AuditAction $action
 * @property string $subject_type
 * @property string|null $subject_id
 * @property array<string, mixed> $event_data
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $request_route
 * @property Carbon $created_at
 * @property-read User|null $user
 * @property-read Organisation|null $organisation
 */
#[Fillable([
    'organisation_id', 'user_id', 'action', 'subject_type', 'subject_id',
    'event_data', 'ip_address', 'user_agent', 'request_route',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        // History is never rewritten
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'event_data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
