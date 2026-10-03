<?php

declare(strict_types=1);

namespace Modules\Fixtures\Queries;

use App\Models\User;
use App\Queries\VisibleTeams;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    /**
     * Not kicked off yet, soonest first.
     *
     * @return Collection<int, Fixture>
     */
    public function upcoming(User $user, int $limit = 50): Collection
    {
        return $this->query($user)->where('kick_off_at', '>=', now())->orderBy('kick_off_at')->limit($limit)->get();
    }

    /**
     * Already kicked off, most recent first.
     *
     * @return Collection<int, Fixture>
     */
    public function recent(User $user, int $limit = 20): Collection
    {
        return $this->query($user)->where('kick_off_at', '<', now())->orderByDesc('kick_off_at')->limit($limit)->get();
    }
}
