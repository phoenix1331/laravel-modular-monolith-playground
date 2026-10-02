<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Club admins read their club's trail and the super admin reads everything.
 * Workers do not see the audit log.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
