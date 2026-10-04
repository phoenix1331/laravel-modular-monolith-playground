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
        // The team decides the club. Set here rather than left to the tenancy
        // auto-fill so seeders, which run without model events, can use this
        // action and announce fixtures exactly as the controller does
        $fixture->organisation_id = $team->organisation_id;
        $fixture->save();

        FixtureScheduled::dispatch($fixture->toData());

        return $fixture;
    }
}
