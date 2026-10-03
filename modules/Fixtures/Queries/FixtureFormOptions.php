<?php

declare(strict_types=1);

namespace Modules\Fixtures\Queries;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Fixtures\Models\Venue;

/**
 * Choices for the fixture form: the teams this user may schedule for, and
 * the club's venues.
 */
final class FixtureFormOptions
{
    /**
     * Admins schedule for any team in the club, coaches for the teams they coach.
     *
     * @return Collection<int, Team>
     */
    public function teamsFor(User $user): Collection
    {
        $query = $user->isAdmin() ? Team::query() : $user->teams();

        return $query->orderBy('name')->get(['teams.id', 'teams.name']);
    }

    /**
     * The team chosen on the form, within the current club. Another club's id
     * is a 404 through the tenancy scope.
     */
    public function team(int $teamId): Team
    {
        return Team::findOrFail($teamId);
    }

    /**
     * @return Collection<int, Venue>
     */
    public function venues(): Collection
    {
        return Venue::query()->orderBy('name')->get(['id', 'name', 'address', 'postcode']);
    }
}
