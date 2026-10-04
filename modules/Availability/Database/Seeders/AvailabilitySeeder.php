<?php

declare(strict_types=1);

namespace Modules\Availability\Database\Seeders;

use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Seeder;
use Modules\Availability\Models\AvailabilityResponse;
use Modules\Availability\Models\AvailabilityStatus;
use Modules\Fixtures\Contracts\FixtureLookup;

/**
 * Responses for each team's upcoming fixtures, tapering off the further away
 * the match is, the way a real squad replies:
 *
 * | Fixture | Replies                                      |
 * |---------|----------------------------------------------|
 * | Next    | All but two players, a few maybes and outs   |
 * | Second  | Half the squad                               |
 * | Third   | None yet                                     |
 *
 * Fixtures are found through the public FixtureLookup contract, the same as
 * the rest of this module. Runs after FixturesSeeder because Fixtures is a
 * hard dependency.
 */
final class AvailabilitySeeder extends Seeder
{
    private const array NOTES = ['Family holiday', 'Injured ankle', 'School trip', 'Grandparent\'s birthday'];

    public function run(ModuleManager $modules, CurrentOrganisation $current, FixtureLookup $fixtures): void
    {
        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if ($modules->isEnabled($organisation, 'availability')) {
                $current->runAs($organisation, fn () => $this->seedClub($organisation, $fixtures));
            }
        }
    }

    private function seedClub(Organisation $organisation, FixtureLookup $fixtures): void
    {
        $teams = Team::query()->where('organisation_id', $organisation->id)->with(['players', 'workers'])->orderBy('id')->get();

        foreach ($teams as $team) {
            $players = $team->players->sortBy('shirt_number')->values();
            $coachId = $team->workers->first()?->id;

            foreach ($fixtures->upcomingForTeam($team->id, 2) as $position => $fixture) {
                $replies = $position === 0 ? $players->count() - 2 : intdiv($players->count(), 2);

                $players->take($replies)->each(
                    fn (Player $player, int $index) => $this->respond($organisation, $fixture->id, $player, $index, $coachId),
                );
            }
        }
    }

    private function respond(Organisation $organisation, int $fixtureId, Player $player, int $index, ?int $coachId): void
    {
        $status = match (true) {
            $index % 7 === 3 => AvailabilityStatus::Unavailable,
            $index % 5 === 4 => AvailabilityStatus::Maybe,
            default => AvailabilityStatus::Available,
        };

        $response = new AvailabilityResponse([
            'fixture_id' => $fixtureId,
            'player_id' => $player->id,
            'status' => $status,
            'note' => $status === AvailabilityStatus::Unavailable ? self::NOTES[$index % count(self::NOTES)] : null,
            'recorded_by' => $coachId,
        ]);
        $response->organisation_id = $organisation->id;
        $response->save();
    }
}
