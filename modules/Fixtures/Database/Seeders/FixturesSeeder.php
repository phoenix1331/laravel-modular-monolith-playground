<?php

declare(strict_types=1);

namespace Modules\Fixtures\Database\Seeders;

use App\Models\Organisation;
use App\Models\Team;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Modules\Fixtures\Actions\ScheduleFixture;
use Modules\Fixtures\Models\Fixture;
use Modules\Fixtures\Models\Venue;

/**
 * A season in progress for every club with Fixtures enabled: a home ground,
 * a county cup venue, and for each team three played and three upcoming
 * weekend fixtures.
 *
 * Played fixtures are written directly: they were announced long ago.
 * Upcoming ones go through ScheduleFixture, the same path as the controller,
 * so FixtureScheduled fires and any module listening (Messaging) reacts as it
 * would in production. Fixtures does not know or care who is listening.
 *
 * Runs without model events (DatabaseSeeder), so organisation_id is set
 * explicitly and nothing is audited.
 */
final class FixturesSeeder extends Seeder
{
    private const array OPPONENTS = [
        'Harbour Town',
        'Millbrook Athletic',
        'Westfield United',
        "St Anne's Colts",
        'Kingsley Park Rangers',
        'Old Quay',
    ];

    /** Weeks from this weekend: three played, three to come */
    private const array WEEKS = [-3, -2, -1, 1, 2, 3];

    public function run(ModuleManager $modules, CurrentOrganisation $current, ScheduleFixture $schedule): void
    {
        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if ($modules->isEnabled($organisation, 'fixtures')) {
                $current->runAs($organisation, fn () => $this->seedClub($organisation, $schedule));
            }
        }
    }

    private function seedClub(Organisation $organisation, ScheduleFixture $schedule): void
    {
        $home = $this->venue($organisation, explode(' ', $organisation->name)[0].' Park', 'Park Lane');
        $cup = $this->venue($organisation, 'County FA Centre', 'Stadium Way');

        $teams = Team::query()->where('organisation_id', $organisation->id)->orderBy('id')->get();

        foreach ($teams as $teamIndex => $team) {
            foreach (self::WEEKS as $weekIndex => $week) {
                $isHome = ($teamIndex + $weekIndex) % 2 === 0;
                $isCup = $week === 2;

                $details = [
                    'opponent' => $this->opponent($team, $teamIndex + $weekIndex),
                    'kick_off_at' => $this->kickOff($team, $week),
                    'is_home' => $isHome,
                    'venue_id' => match (true) {
                        $isCup => $cup->id,
                        $isHome => $home->id,
                        default => null,
                    },
                    'competition' => $isCup ? 'County Cup' : 'League',
                ];

                if ($week > 0) {
                    $schedule->handle($team, $details);

                    continue;
                }

                $fixture = new Fixture($details);
                $fixture->team()->associate($team);
                $fixture->organisation_id = $organisation->id;
                $fixture->save();
            }
        }
    }

    private function venue(Organisation $organisation, string $name, string $street): Venue
    {
        $venue = new Venue([
            'name' => $name,
            'address' => fake()->buildingNumber().' '.$street.', '.fake()->city(),
            'postcode' => fake()->postcode(),
        ]);
        $venue->organisation_id = $organisation->id;
        $venue->save();

        return $venue;
    }

    /**
     * Junior and girls' sides play the same age group at the other club.
     */
    private function opponent(Team $team, int $index): string
    {
        $club = self::OPPONENTS[$index % count(self::OPPONENTS)];

        return str_starts_with($team->age_group, 'U') ? "{$club} {$team->age_group}" : $club;
    }

    /**
     * Juniors on Saturday mornings, adults on Sunday afternoons.
     *
     * Counted from the next Saturday, which is always in the future. Played
     * weeks skip the weekend just gone, so a "played" fixture can never land
     * later today whatever day the seeder runs.
     */
    private function kickOff(Team $team, int $week): CarbonImmutable
    {
        $saturday = CarbonImmutable::today()->next(CarbonImmutable::SATURDAY)->addWeeks($week - 1);

        return str_starts_with($team->age_group, 'U')
            ? $saturday->setTime(10, 30)
            : $saturday->addDay()->setTime(14, 0);
    }
}
