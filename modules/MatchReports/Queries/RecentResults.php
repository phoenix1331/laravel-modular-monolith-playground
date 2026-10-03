<?php

declare(strict_types=1);

namespace Modules\MatchReports\Queries;

use App\Models\Team;
use App\Models\User;
use App\Queries\VisibleTeams;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Contracts\FixtureLookup;
use Modules\MatchReports\Models\MatchReport;

/**
 * Played fixtures for the user's teams, newest first, each with its report
 * if one has been filed. Fixtures come from the public lookup; reports from
 * this module's table; they are matched up in PHP.
 */
final class RecentResults
{
    public function __construct(
        private readonly VisibleTeams $teams,
        private readonly FixtureLookup $fixtures,
    ) {}

    /**
     * @return list<array{fixture: FixtureData, report: MatchReport|null}>
     */
    public function for(User $user, int $limit = 20): array
    {
        $played = $this->teams->for($user)->get()
            ->flatMap(fn (Team $team) => $this->fixtures->forTeam($team->id))
            ->filter(fn (FixtureData $fixture) => $fixture->hasKickedOff())
            ->sortByDesc(fn (FixtureData $fixture) => $fixture->kickOffAt->getTimestamp())
            ->take($limit)
            ->values();

        $reports = MatchReport::query()
            ->whereIn('fixture_id', $played->map(fn (FixtureData $fixture) => $fixture->id))
            ->get()
            ->keyBy('fixture_id');

        return array_values($played->map(fn (FixtureData $fixture) => [
            'fixture' => $fixture,
            'report' => $reports->get($fixture->id),
        ])->all());
    }
}
