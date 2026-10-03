<?php

declare(strict_types=1);

namespace Modules\Fixtures\Queries;

use App\Models\User;
use App\Queries\VisibleTeams;
use Illuminate\Database\Eloquent\Builder;
use Modules\Fixtures\Models\Fixture;

/**
 * Fixtures for the teams a user can see. Reuses Core's VisibleTeams so the
 * "workers only see their teams" rule is defined once, in Core.
 */
final class FixturesForUser
{
    public function __construct(private readonly VisibleTeams $teams) {}

    /**
     * @return Builder<Fixture>
     */
    public function query(User $user): Builder
    {
        return Fixture::query()
            ->with(['team:id,name', 'venue:id,name'])
            ->whereIn('team_id', $this->teams->for($user)->select('teams.id'));
    }
}
