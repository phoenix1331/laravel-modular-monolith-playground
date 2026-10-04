<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Messaging\Models\Announcement;

/*
| Fixtures and Subscriptions are driven through their public routes and
| command, so these tests see exactly what Messaging sees: published events.
| Both are soft dependencies: a test that needs one skips when it is not
| installed, and the rest of Messaging is still tested on its own.
*/

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    $modules = app(ModuleManager::class);
    foreach (['fixtures', 'subscriptions'] as $softDependency) {
        if (app(ModuleRegistry::class)->has($softDependency)) {
            $modules->enable($this->club, $softDependency);
        }
    }
    $modules->enable($this->club, 'messaging');

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->team = Team::factory()->for($this->club)->create(['name' => 'U12 Lions']);
    $this->team->workers()->attach($this->coach);
    $this->otherTeam = Team::factory()->for($this->club)->create(['name' => 'U14 Hawks']);
    Player::factory()->for($this->team)->create(['first_name' => 'Sam', 'last_name' => 'Jones']);
});

function messagingScheduleFixture(Team $team): void
{
    test()->actingAs(test()->admin)->post(route('fixtures.store'), [
        'team_id' => $team->id,
        'opponent' => 'Harbour Town',
        'kick_off_at' => now()->addWeek()->setTime(10, 30)->format('Y-m-d H:i'),
        'is_home' => true,
    ])->assertSessionHasNoErrors();
}

function messagingOverdueFee(Team $team): void
{
    test()->actingAs(test()->admin)->post(route('subscriptions.fees.store'), [
        'team_id' => $team->id,
        'description' => 'Autumn subs',
        'amount' => '45.00',
        'due_on' => now()->subWeek()->toDateString(),
    ])->assertSessionHasNoErrors();

    auth()->logout();
    test()->artisan('subscriptions:check-overdue')->assertSuccessful();
}

describe('reacting to other modules\' events', function () {
    test('a scheduled fixture is announced to the team', function () {
        $this->skipUnlessModuleInstalled('fixtures');

        messagingScheduleFixture($this->team);

        $announcement = Announcement::sole();

        expect($announcement->source)->toBe('fixtures')
            ->and($announcement->audience)->toBe('team')
            ->and($announcement->team_id)->toBe($this->team->id)
            ->and($announcement->title)->toBe('New fixture: U12 Lions v Harbour Town')
            ->and($announcement->organisation_id)->toBe($this->club->id);
    });

    test('an overdue payment becomes an admins-only reminder, even from a console command', function () {
        $this->skipUnlessModuleInstalled('subscriptions');

        messagingOverdueFee($this->team);

        $reminder = Announcement::withoutOrganisationScope()->where('source', 'subscriptions')->sole();

        expect($reminder->audience)->toBe('admins')
            ->and($reminder->title)->toBe('Overdue: Sam Jones')
            ->and($reminder->body)->toContain('£45.00')
            ->and($reminder->organisation_id)->toBe($this->club->id);
    });

    test('with messaging disabled for the club, events post nothing', function () {
        $this->skipUnlessModuleInstalled('fixtures');
        $this->skipUnlessModuleInstalled('subscriptions');

        app(ModuleManager::class)->disable($this->club, 'messaging');

        messagingScheduleFixture($this->team);
        messagingOverdueFee($this->team);

        expect(Announcement::withoutOrganisationScope()->count())->toBe(0);
    });

    test('another club\'s events never land on this club\'s board', function () {
        $this->skipUnlessModuleInstalled('fixtures');

        auth()->logout();
        $otherClub = Organisation::factory()->create();
        app(ModuleManager::class)->enable($otherClub, 'fixtures');
        $otherAdmin = User::factory()->admin()->forOrganisation($otherClub)->create();
        $otherTeam = Team::factory()->for($otherClub)->create();

        $this->actingAs($otherAdmin)->post(route('fixtures.store'), [
            'team_id' => $otherTeam->id, 'opponent' => 'Elsewhere', 'kick_off_at' => now()->addWeek()->format('Y-m-d H:i'), 'is_home' => true,
        ]);

        expect(Announcement::withoutOrganisationScope()->count())->toBe(0);
    });
});

describe('the noticeboard', function () {
    test('coaches see club-wide and their own teams\' posts, never admin notices', function () {
        $this->skipUnlessModuleInstalled('fixtures');
        $this->skipUnlessModuleInstalled('subscriptions');

        messagingScheduleFixture($this->team);
        messagingScheduleFixture($this->otherTeam);
        messagingOverdueFee($this->team);
        $this->actingAs($this->admin)->post(route('messaging.store'), ['title' => 'Club AGM on Friday']);

        $this->actingAs($this->coach)->get(route('messaging.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('messaging/pages/Index')
                ->where('announcements', fn ($rows) => collect($rows)->pluck('title')->sort()->values()->all() === [
                    'Club AGM on Friday',
                    'New fixture: U12 Lions v Harbour Town',
                ]));

        $this->actingAs($this->admin)->get(route('messaging.index'))
            ->assertInertia(fn (Assert $page) => $page->has('announcements', 4));
    });

    test('coaches post only to teams they coach; admins also club-wide', function () {
        $this->actingAs($this->coach);
        $this->post(route('messaging.store'), ['team_id' => $this->team->id, 'title' => 'Training moved'])->assertRedirect();
        $this->post(route('messaging.store'), ['team_id' => $this->otherTeam->id, 'title' => 'Not mine'])->assertForbidden();
        $this->post(route('messaging.store'), ['title' => 'Whole club'])->assertForbidden();

        $this->actingAs($this->admin)->post(route('messaging.store'), ['title' => 'Whole club'])->assertRedirect();

        expect(Announcement::pluck('title')->sort()->values()->all())->toBe(['Training moved', 'Whole club']);
    });

    test('authors and admins can remove posts; other coaches cannot', function () {
        $this->actingAs($this->coach)->post(route('messaging.store'), ['team_id' => $this->team->id, 'title' => 'Mine']);
        $post = Announcement::sole();

        $otherCoach = User::factory()->worker()->forOrganisation($this->club)->create();
        $this->team->workers()->attach($otherCoach);

        $this->actingAs($otherCoach)->delete(route('messaging.destroy', $post))->assertForbidden();
        $this->actingAs($this->coach)->delete(route('messaging.destroy', $post))->assertRedirect();
        expect(Announcement::count())->toBe(0);
    });

    test('messaging works on its own, with neither soft dependency enabled', function () {
        auth()->logout();
        $club = Organisation::factory()->create();
        app(ModuleManager::class)->enable($club, 'messaging');
        $admin = User::factory()->admin()->forOrganisation($club)->create();

        $this->actingAs($admin)->post(route('messaging.store'), ['title' => 'Hello'])->assertRedirect();
        $this->get(route('messaging.index'))->assertInertia(fn (Assert $page) => $page->has('announcements', 1));
    });
});
