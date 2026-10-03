<?php

declare(strict_types=1);

namespace Modules\Fixtures\Actions;

use App\Models\Team;
use Modules\Fixtures\Contracts\Events\FixtureScheduled;
use Modules\Fixtures\Models\Fixture;

/**
 * Schedules a fixture and announces it.
 *
 * Writing and announcing live together so every way of scheduling (the
 * controller today, an import or API later) fires the same public event.
 * Fixtures does not know who listens.
 */
final class ScheduleFixture
{
    /**
     * @param  array<string, mixed>  $details  validated fixture attributes (opponent, kick_off_at, is_home, venue_id, competition)
     */
    public function handle(Team $team, array $details): Fixture
    {
        $fixture = new Fixture($details);
        $fixture->team()->associate($team);
        $fixture->save();

        FixtureScheduled::dispatch($fixture->toData());

        return $fixture;
    }
}
