<?php

declare(strict_types=1);

namespace Modules\MatchReports\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Fixtures\Contracts\FixtureData;

/**
 * Same shape as Availability's policy: rules take FixtureData and reuse
 * Core's team rules. Filing adds one module rule: the match must have
 * kicked off.
 */
class MatchReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isWorker();
    }

    public function view(User $user, FixtureData $fixture): bool
    {
        $team = Team::find($fixture->teamId);

        return $team !== null && $user->can('view', $team);
    }

    public function file(User $user, FixtureData $fixture): bool
    {
        if ($user->organisation_id !== $fixture->organisationId || ! $fixture->hasKickedOff()) {
            return false;
        }

        return $user->isAdmin() || $user->coachesTeam($fixture->teamId);
    }
}
