<?php

declare(strict_types=1);

namespace Modules\Messaging\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Messaging\Models\Announcement;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isWorker();
    }

    /**
     * Admins post club-wide or to any team; coaches only to teams they coach.
     */
    public function create(User $user, ?Team $team = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $team !== null && $user->coachesTeam($team->id);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->organisation_id === $announcement->organisation_id
            && ($user->isAdmin() || $announcement->author_id === $user->id);
    }
}
