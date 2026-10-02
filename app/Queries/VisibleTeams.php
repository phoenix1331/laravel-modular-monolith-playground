<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The teams a club user can see: all of them for an admin, only their
 * assigned teams for a worker. Mirrors TeamPolicy::view() for lists, where
 * checking the policy row by row would be wasteful.
 *
 * Controllers ask query classes for data rather than building Eloquent
 * queries inline, so access rules for lists live in one place.
 */
class VisibleTeams
{
    /**
     * @return Builder<Team>
     */
    public function for(User $user): Builder
    {
        $query = Team::query()->withCount('players')->orderBy('name');

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('workers', fn (Builder $workers) => $workers->whereKey($user->id));
    }
}
