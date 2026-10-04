<?php

declare(strict_types=1);

namespace Modules\Messaging\Database\Seeders;

use App\Enums\Role;
use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Seeder;
use Modules\Messaging\Models\Announcement;

/**
 * The hand-written half of the noticeboard: club-wide notices from the admin
 * and a post from each team's coach, dated over the past fortnight.
 *
 * The automatic half is not written here. FixturesSeeder schedules upcoming
 * fixtures and SubscriptionsSeeder marks fees overdue through their real
 * actions, the public events fire, and Messaging's listeners post them, so
 * the seeded board shows the integration working rather than imitating it.
 * DatabaseSeeder runs this after both (soft dependencies).
 */
final class MessagingSeeder extends Seeder
{
    private const array CLUB_NOTICES = [
        ['Christmas social', 'Family evening at the clubhouse in December, details to follow. Volunteers welcome.', 12],
        ['Pitch maintenance', 'Main pitch closed for re-seeding next week. Training moves to the 3G.', 4],
    ];

    private const array TEAM_POSTS = [
        'Training moved to Thursday this week, same time.',
        'Please bring water bottles labelled with names.',
        'Great effort on Saturday, well done everyone.',
        'Lifts needed for the away game, reply here if you can help.',
    ];

    public function run(ModuleManager $modules, CurrentOrganisation $current): void
    {
        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if ($modules->isEnabled($organisation, 'messaging')) {
                $current->runAs($organisation, fn () => $this->seedClub($organisation));
            }
        }
    }

    private function seedClub(Organisation $organisation): void
    {
        $admin = User::query()->where('organisation_id', $organisation->id)->where('role', Role::Admin)->first();

        foreach (self::CLUB_NOTICES as [$title, $body, $daysAgo]) {
            $this->post($organisation, null, $admin?->id, $title, $body, $daysAgo);
        }

        $teams = Team::query()->where('organisation_id', $organisation->id)->with('workers')->orderBy('id')->get();

        foreach ($teams as $index => $team) {
            $this->post($organisation, $team, $team->workers->first()?->id, self::TEAM_POSTS[$index % count(self::TEAM_POSTS)], null, 2 + $index);
        }
    }

    private function post(Organisation $organisation, ?Team $team, ?int $authorId, string $title, ?string $body, int $daysAgo): void
    {
        $announcement = new Announcement([
            'team_id' => $team?->id,
            'audience' => Announcement::AUDIENCE_TEAM,
            'source' => 'manual',
            'title' => $title,
            'body' => $body,
            'author_id' => $authorId,
        ]);
        $announcement->organisation_id = $organisation->id;
        $announcement->created_at = now()->subDays($daysAgo);
        $announcement->save();
    }
}
