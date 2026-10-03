<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who is in a team: its players and the workers who coach it.
 */
class TeamRoster
{
    /**
     * @return Collection<int, Player>
     */
    public function players(Team $team): Collection
    {
        return $team->players()->orderBy('last_name')->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function coaches(Team $team): Collection
    {
        return $team->workers()->orderBy('name')->get(['users.id', 'users.name']);
    }

    /**
     * @return list<int>
     */
    public function coachIds(Team $team): array
    {
        return array_values($team->workers()->pluck('users.id')->map(fn ($id) => (int) $id)->all());
    }
}
