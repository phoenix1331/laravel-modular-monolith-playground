<?php

declare(strict_types=1);

namespace Modules\Availability\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Fixtures\Contracts\FixtureData;

/**
 * Rules take FixtureData, the public shape, because Availability never holds
 * a Fixture model. The team is Core, so its rules are reused directly.
 */
class AvailabilityResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isWorker();
    }

    /**
     * Anyone who can see the team can see who is available.
     */
    public function view(User $user, FixtureData $fixture): bool
    {
        $team = Team::find($fixture->teamId);

        return $team !== null && $user->can('view', $team);
    }

    /**
     * Admins and the team's coaches record answers on the players' behalf.
     */
    public function mark(User $user, FixtureData $fixture): bool
    {
        if ($user->organisation_id !== $fixture->organisationId) {
            return false;
        }

        return $user->isAdmin()
            || $user->coachesTeam($fixture->teamId);
    }
}
