<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Audit entries a user may read: their own club's trail for an admin, every
 * entry (platform and all clubs) for the super admin.
 *
 * AuditLog is not tenant-scoped, so this is the one place that filters it.
 */
class AuditTrail
{
    /**
     * @return Builder<AuditLog>
     */
    public function for(User $user): Builder
    {
        $query = AuditLog::query()
            ->with(['user:id,name', 'organisation:id,name'])
            ->latest('id');

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('organisation_id', $user->organisation_id);
    }
}
