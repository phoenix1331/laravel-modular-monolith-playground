<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Modules\Exceptions\ModuleDependencyNotEnabled;
use App\Modules\Exceptions\ModuleHasEnabledDependents;
use App\Modules\ModuleManager;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Availability\Models\AvailabilityResponse;
use Modules\Fixtures\Contracts\FixtureLookup;

/*
| These tests treat Fixtures as a black box: fixtures are scheduled through
| its HTTP route and read back through its public FixtureLookup, exactly as
| the Availability code does. Nothing here touches Fixtures' internals.
*/

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    $modules = app(ModuleManager::class);
    $modules->enable($this->club, 'fixtures');
    $modules->enable($this->club, 'availability');

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->team = Team::factory()->for($this->club)->create(['name' => 'U12 Lions']);
    $this->team->workers()->attach($this->coach);
    $this->otherTeam = Team::factory()->for($this->club)->create();

    $this->sam = Player::factory()->for($this->team)->create(['first_name' => 'Sam', 'last_name' => 'Jones']);
    $this->alex = Player::factory()->for($this->team)->create(['first_name' => 'Alex', 'last_name' => 'Smith']);

    $this->fixtureId = scheduleFixture($this->admin, $this->team);
});

/**
 * Schedule through Fixtures' public route and return the id from its public lookup.
 */
function scheduleFixture(User $as, Team $team, string $opponent = 'Harbour Town'): int
{
    test()->actingAs($as)->post(route('fixtures.store'), [
        'team_id' => $team->id,
        'opponent' => $opponent,
        'kick_off_at' => now()->addWeek()->format('Y-m-d H:i'),
        'is_home' => true,
    ])->assertSessionHasNoErrors();

    return collect(app(FixtureLookup::class)->forTeam($team->id))->firstWhere('opponent', $opponent)->id;
}

describe('dependency on fixtures', function () {
    test('availability cannot be enabled before fixtures', function () {
        app(ModuleManager::class)->enable(Organisation::factory()->create(), 'availability');
    })->throws(ModuleDependencyNotEnabled::class);

    test('fixtures cannot be disabled while availability is on', function () {
        app(ModuleManager::class)->disable($this->club, 'fixtures');
    })->throws(ModuleHasEnabledDependents::class);

    test('a club with fixtures but not availability gets a 404', function () {
        $club = Organisation::factory()->create();
        app(ModuleManager::class)->enable($club, 'fixtures');

        $this->actingAs(User::factory()->admin()->forOrganisation($club)->create())
            ->get(route('availability.index'))
            ->assertNotFound();
    });

    test('both modules appear in enabledModules, dependency first', function () {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('enabledModules', [
                ['key' => 'fixtures', 'name' => 'Fixtures'],
                ['key' => 'availability', 'name' => 'Availability'],
            ]));
    });
});

describe('recording availability', function () {
    test('the fixture page lists the whole squad, unanswered first time round', function () {
        $this->actingAs($this->coach)->get(route('availability.show', $this->fixtureId))
            ->assertInertia(fn (Assert $page) => $page
                ->component('availability/pages/Fixture')
                ->where('fixture.opponent', 'Harbour Town')
                ->has('players', 2)
                ->where('players.0.status', null)
                ->where('can.mark', true));
    });

    test('a coach marks players and the summary counts them', function () {
        $this->actingAs($this->coach);

        $this->put(route('availability.update', [$this->fixtureId, $this->sam]), ['status' => 'available'])->assertRedirect();
        $this->put(route('availability.update', [$this->fixtureId, $this->alex]), ['status' => 'unavailable', 'note' => 'On holiday'])->assertRedirect();
        $this->put(route('availability.update', [$this->fixtureId, $this->sam]), ['status' => 'maybe'])->assertRedirect();

        expect(AvailabilityResponse::count())->toBe(2)
            ->and(AvailabilityResponse::where('player_id', $this->sam->id)->sole()->recorded_by)->toBe($this->coach->id);

        $this->get(route('availability.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('availability/pages/Index')
                ->where('fixtures.0.squad', 2)
                ->where('fixtures.0.counts', ['available' => 0, 'maybe' => 1, 'unavailable' => 1]));
    });

    test('answers are audited under the module\'s type', function () {
        $this->actingAs($this->coach)->put(route('availability.update', [$this->fixtureId, $this->sam]), ['status' => 'available']);

        expect(AuditLog::where('subject_type', 'availability.response')->value('organisation_id'))->toBe($this->club->id);
    });

    test('the status must be one of the known answers', function () {
        $this->actingAs($this->coach)
            ->put(route('availability.update', [$this->fixtureId, $this->sam]), ['status' => 'injured'])
            ->assertSessionHasErrors('status');
    });
});

describe('who can see and mark', function () {
    test('a coach of another team can neither view nor mark', function () {
        $otherCoach = User::factory()->worker()->forOrganisation($this->club)->create();
        $this->otherTeam->workers()->attach($otherCoach);

        $this->actingAs($otherCoach);
        $this->get(route('availability.show', $this->fixtureId))->assertForbidden();
        $this->put(route('availability.update', [$this->fixtureId, $this->sam]), ['status' => 'available'])->assertForbidden();
    });

    test('a coach only sees fixtures for the teams they coach', function () {
        scheduleFixture($this->admin, $this->otherTeam, 'Not My Match FC');

        $this->actingAs($this->coach)->get(route('availability.index'))
            ->assertInertia(fn (Assert $page) => $page->has('fixtures', 1)->where('fixtures.0.opponent', 'Harbour Town'));
    });

    test('a player from another team cannot be marked for this fixture', function () {
        $outsider = Player::factory()->for($this->otherTeam)->create();

        $this->actingAs($this->admin)
            ->put(route('availability.update', [$this->fixtureId, $outsider]), ['status' => 'available'])
            ->assertNotFound();
    });

    test('another club\'s fixture is a 404', function () {
        // Signed out first: the tenancy guard rightly refuses to create another club's records while signed in to this one
        auth()->logout();
        $otherClub = Organisation::factory()->create();
        app(ModuleManager::class)->enable($otherClub, 'fixtures');
        app(ModuleManager::class)->enable($otherClub, 'availability');
        $foreignAdmin = User::factory()->admin()->forOrganisation($otherClub)->create();
        $foreignFixtureId = scheduleFixture($foreignAdmin, Team::factory()->for($otherClub)->create());

        $this->actingAs($this->admin)->get(route('availability.show', $foreignFixtureId))->assertNotFound();
    });
});
