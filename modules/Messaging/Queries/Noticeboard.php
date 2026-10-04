<?php

declare(strict_types=1);

namespace Modules\Messaging\Queries;

use App\Models\Team;
use App\Models\User;
use App\Queries\VisibleTeams;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Messaging\Models\Announcement;

/**
 * What a user sees on the noticeboard: admins see everything; coaches see
 * club-wide posts and their own teams' posts, never admin-only notices.
 */
final class Noticeboard
{
    public function __construct(private readonly VisibleTeams $teams) {}

    /**
     * @return Collection<int, Announcement>
     */
    public function for(User $user): Collection
    {
        $query = Announcement::query()->with(['team:id,name', 'author:id,name'])->latest('id')->limit(50);

        if (! $user->isAdmin()) {
            $query->where('audience', Announcement::AUDIENCE_TEAM)
                ->where(fn (Builder $visible) => $visible
                    ->whereNull('team_id')
                    ->orWhereIn('team_id', $this->teams->for($user)->select('teams.id')));
        }

        return $query->get();
    }

    /**
     * Teams the user may post to: every team for admins, their own for coaches.
     *
     * @return Collection<int, Team>
     */
    public function postableTeams(User $user): Collection
    {
        return $this->teams->for($user)->get(['teams.id', 'teams.name']);
    }

    public function team(?int $teamId): ?Team
    {
        return $teamId === null ? null : Team::findOrFail($teamId);
    }
}
