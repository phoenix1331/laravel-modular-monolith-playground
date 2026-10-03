<?php

declare(strict_types=1);

namespace Modules\Availability\Queries;

use App\Models\Team;
use App\Models\User;
use App\Queries\VisibleTeams;
use Modules\Availability\Models\AvailabilityResponse;
use Modules\Availability\Models\AvailabilityStatus;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Contracts\FixtureLookup;

/**
 * Upcoming fixtures for the user's teams, each with a count of answers.
 *
 * Fixtures come from the public FixtureLookup (another module); responses
 * come from this module's own table. The two are joined here in PHP rather
 * than in SQL, because a SQL join would reach into Fixtures' table.
 */
final class UpcomingAvailability
{
    public function __construct(
        private readonly VisibleTeams $teams,
        private readonly FixtureLookup $fixtures,
    ) {}

    /**
     * @return list<array{fixture: FixtureData, squad: int, counts: array<string, int>}>
     */
    public function for(User $user, int $perTeam = 5): array
    {
        $teams = $this->teams->for($user)->get();

        $fixtures = $teams
            ->flatMap(fn (Team $team) => $this->fixtures->upcomingForTeam($team->id, $perTeam))
            ->sortBy(fn (FixtureData $fixture) => $fixture->kickOffAt->getTimestamp())
            ->values();

        $counts = AvailabilityResponse::query()
            ->whereIn('fixture_id', $fixtures->map(fn (FixtureData $fixture) => $fixture->id))
            ->selectRaw('fixture_id, status, count(*) as total')
            ->groupBy('fixture_id', 'status')
            ->get()
            ->groupBy('fixture_id');

        $squads = $teams->mapWithKeys(fn (Team $team) => [$team->id => (int) $team->players_count]);

        return array_values($fixtures->map(function (FixtureData $fixture) use ($counts, $squads) {
            $byStatus = array_fill_keys(array_column(AvailabilityStatus::cases(), 'value'), 0);

            foreach ($counts->get($fixture->id, []) as $row) {
                $byStatus[$row->status->value] = (int) $row->getAttribute('total');
            }

            return ['fixture' => $fixture, 'squad' => $squads[$fixture->teamId] ?? 0, 'counts' => $byStatus];
        })->all());
    }
}
