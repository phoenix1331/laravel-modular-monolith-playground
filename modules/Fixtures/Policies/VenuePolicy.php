<?php

declare(strict_types=1);

namespace Modules\Fixtures\Policies;

use App\Models\User;
use Modules\Fixtures\Models\Venue;

/**
 * Venues are club-wide settings, so only admins manage them. Everyone picks
 * from them when scheduling.
 */
class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->isAdmin() && $user->organisation_id === $venue->organisation_id;
    }
}
