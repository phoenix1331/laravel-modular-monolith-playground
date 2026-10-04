<?php

declare(strict_types=1);

namespace Modules\Messaging\Listeners;

use Modules\Fixtures\Contracts\Events\FixtureScheduled;
use Modules\Messaging\Models\Announcement;

/**
 * Tells the team about a newly scheduled fixture. Reads only the public
 * FixtureData carried by the event.
 */
final class AnnounceFixture
{
    use PostsForOrganisation;

    public function handle(FixtureScheduled $event): void
    {
        $fixture = $event->fixture;
        $where = $fixture->venue !== null ? " at {$fixture->venue}" : ($fixture->isHome ? ' (home)' : ' (away)');

        $this->postFor($fixture->organisationId, [
            'team_id' => $fixture->teamId,
            'audience' => Announcement::AUDIENCE_TEAM,
            'source' => 'fixtures',
            'title' => "New fixture: {$fixture->teamName} ".($fixture->isHome ? 'v' : '@')." {$fixture->opponent}",
            'body' => 'Kick-off '.$fixture->kickOffAt->format('l j F, H:i').$where.'.',
        ]);
    }
}
