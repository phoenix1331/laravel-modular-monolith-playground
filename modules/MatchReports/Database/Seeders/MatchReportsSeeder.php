<?php

declare(strict_types=1);

namespace Modules\MatchReports\Database\Seeders;

use App\Models\Organisation;
use App\Models\Team;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Seeder;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Contracts\FixtureLookup;
use Modules\MatchReports\Models\MatchReport;
use Modules\MatchReports\Models\Performance;

/**
 * Reports for every played fixture except each team's most recent, which is
 * left for the coach to file so the "awaiting report" state is visible.
 *
 * Every player in the squad gets a rating; the goals are shared out among
 * them so scorers always add up to the score. Played fixtures come from the
 * public FixtureLookup contract.
 */
final class MatchReportsSeeder extends Seeder
{
    private const array SUMMARIES = [
        'won' => ['Dominated possession and deserved the points.', 'Came from behind with a strong second half.'],
        'drew' => ['Even contest, both keepers kept us in it.', 'Shared the points in tricky wind.'],
        'lost' => ['Started slowly and never quite recovered.', 'Plenty of effort, lessons for training.'],
    ];

    public function run(ModuleManager $modules, CurrentOrganisation $current, FixtureLookup $fixtures): void
    {
        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if ($modules->isEnabled($organisation, 'match-reports')) {
                $current->runAs($organisation, fn () => $this->seedClub($organisation, $fixtures));
            }
        }
    }

    private function seedClub(Organisation $organisation, FixtureLookup $fixtures): void
    {
        $teams = Team::query()->where('organisation_id', $organisation->id)->with(['players', 'workers'])->orderBy('id')->get();

        foreach ($teams as $team) {
            $played = array_values(array_filter($fixtures->forTeam($team->id), fn (FixtureData $fixture) => $fixture->hasKickedOff()));

            foreach (array_slice($played, 0, -1) as $fixture) {
                $this->report($organisation, $team, $fixture);
            }
        }
    }

    private function report(Organisation $organisation, Team $team, FixtureData $fixture): void
    {
        $for = fake()->numberBetween(0, 4);
        $against = fake()->numberBetween(0, 3);

        $report = new MatchReport([
            'fixture_id' => $fixture->id,
            'goals_for' => $for,
            'goals_against' => $against,
            'recorded_by' => $team->workers->first()?->id,
        ]);
        $report->organisation_id = $organisation->id;
        $report->summary = fake()->randomElement(self::SUMMARIES[$report->result()]);
        $report->save();

        // Shirts 7 to 11 are the attackers; faker (seeded) keeps it repeatable
        $players = $team->players->sortBy('shirt_number')->values();
        $attackers = $players->slice(6, 5)->pluck('id')->all();
        $goals = $for === 0 ? [] : array_count_values(array_map(
            fn () => fake()->randomElement($attackers),
            range(1, $for),
        ));

        foreach ($players as $player) {
            $performance = new Performance([
                'player_id' => $player->id,
                'goals' => $goals[$player->id] ?? 0,
                'rating' => fake()->numberBetween(5, 9),
            ]);
            $performance->organisation_id = $organisation->id;
            $performance->report_id = $report->id;
            $performance->save();
        }
    }
}
