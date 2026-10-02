<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;

/**
 * Organisations are managed from the platform, so only the super admin can
 * list them or change which modules they have.
 */
class OrganisationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Organisation $organisation): bool
    {
        return $user->isSuperAdmin();
    }

    public function manageModules(User $user, Organisation $organisation): bool
    {
        return $user->isSuperAdmin();
    }
}
