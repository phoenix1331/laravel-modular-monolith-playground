<?php

declare(strict_types=1);

namespace Modules\Fixtures\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Fixtures\Models\Fixture;

/**
 * Found by Laravel's policy discovery: Modules\Fixtures\Models\Fixture maps
 * to Modules\Fixtures\Policies\FixturePolicy, so nothing is registered.
 *
 * Builds on Core's rules instead of repeating them: seeing a fixture means
 * seeing its team (Core's TeamPolicy). Managing one adds a module-specific
 * rule: admins, and coaches assigned to that team.
 */
class FixturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isWorker();
    }

    public function view(User $user, Fixture $fixture): bool
    {
        return $user->can('view', $fixture->team);
    }

    /**
     * Whether the user may schedule fixtures for any team, e.g. to show the
     * "Schedule fixture" button.
     */
    public function createAny(User $user): bool
    {
        return $user->isAdmin() || ($user->isWorker() && $user->teams()->exists());
    }

    public function create(User $user, Team $team): bool
    {
        return $this->managesTeam($user, $team);
    }

    public function update(User $user, Fixture $fixture): bool
    {
        return $this->managesTeam($user, $fixture->team);
    }

    public function delete(User $user, Fixture $fixture): bool
    {
        return $this->update($user, $fixture);
    }

    private function managesTeam(User $user, Team $team): bool
    {
        if ($user->organisation_id !== $team->organisation_id) {
            return false;
        }

        return $user->isAdmin()
            || ($user->isWorker() && $team->workers()->whereKey($user->id)->exists());
    }
}
