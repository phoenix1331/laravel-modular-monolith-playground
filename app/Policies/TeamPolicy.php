<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Who can do what with a team, inside one club.
 *
 * The organisation boundary is enforced twice: route model binding goes
 * through OrganisationScope, so another club's team is a 404 before this
 * policy runs, and every check below also compares organisation ids. The
 * second check costs nothing and protects code paths that load a team
 * without the scope.
 */
class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isWorker();
    }

    /**
     * Admins see every team in their club. Workers see the teams they coach.
     */
    public function view(User $user, Team $team): bool
    {
        if (! $this->sameOrganisation($user, $team)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->coachesTeam($team->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin() && $this->sameOrganisation($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }

    private function sameOrganisation(User $user, Team $team): bool
    {
        return $user->organisation_id === $team->organisation_id;
    }
}
