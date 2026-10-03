<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Fixtures\Contracts\Events\FixtureScheduled;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Contracts\FixtureLookup;
use Modules\Fixtures\Models\Fixture;
use Modules\Fixtures\Models\Venue;

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    $this->otherClub = Organisation::factory()->create();
    app(ModuleManager::class)->enable($this->club, 'fixtures');
    app(ModuleManager::class)->enable($this->otherClub, 'fixtures');

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->coachedTeam = Team::factory()->for($this->club)->create(['name' => 'U12 Lions']);
    $this->coachedTeam->workers()->attach($this->coach);
    $this->otherTeam = Team::factory()->for($this->club)->create(['name' => 'U14 Hawks']);

    $this->foreignFixture = Fixture::factory()->for(Team::factory()->for($this->otherClub))->create();
});

function fixtureDetails(array $overrides = []): array
{
    return [
        'opponent' => 'Harbour Town',
        'kick_off_at' => now()->addWeek()->format('Y-m-d H:i'),
        'is_home' => true,
        'competition' => 'League',
        ...$overrides,
    ];
}

describe('module gating', function () {
    test('a club without the module gets a 404 and no navigation', function () {
        $club = Organisation::factory()->create();
        $admin = User::factory()->admin()->forOrganisation($club)->create();

        $this->actingAs($admin)->get(route('fixtures.index'))->assertNotFound();
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('enabledModules', []));
    });

    test('a club with the module sees it in enabledModules', function () {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('enabledModules', [['key' => 'fixtures', 'name' => 'Fixtures']]));
    });

    test('switching the module off and on again keeps the club\'s fixtures', function () {
        Fixture::factory()->for($this->coachedTeam)->create(['opponent' => 'Survivors FC']);
        $modules = app(ModuleManager::class);

        $modules->disable($this->club, 'fixtures');
        $this->actingAs($this->admin)->get(route('fixtures.index'))->assertNotFound();

        $modules->enable($this->club, 'fixtures');
        $this->actingAs($this->admin)->get(route('fixtures.index'))
            ->assertInertia(fn (Assert $page) => $page->where('upcoming.0.opponent', 'Survivors FC'));
    });
});

describe('listing', function () {
    test('fixtures render the module page with upcoming and recent matches', function () {
        Fixture::factory()->for($this->coachedTeam)->create(['opponent' => 'Next Week FC']);
        Fixture::factory()->for($this->coachedTeam)->played()->create(['opponent' => 'Last Week FC']);

        $this->actingAs($this->admin)->get(route('fixtures.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('fixtures/pages/Index')
                ->has('upcoming', 1)
                ->where('upcoming.0.opponent', 'Next Week FC')
                ->has('results', 1)
                ->where('results.0.opponent', 'Last Week FC')
                ->where('can', ['create' => true, 'manageVenues' => true]));
    });

    test('a coach only sees fixtures for the teams they coach', function () {
        Fixture::factory()->for($this->coachedTeam)->create(['opponent' => 'Mine FC']);
        Fixture::factory()->for($this->otherTeam)->create(['opponent' => 'Not Mine FC']);

        $this->actingAs($this->coach)->get(route('fixtures.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcoming', 1)
                ->where('upcoming.0.opponent', 'Mine FC')
                ->where('can.manageVenues', false));
    });
});

describe('scheduling', function () {
    test('an admin schedules a fixture and the public event fires with a DTO', function () {
        Event::fake([FixtureScheduled::class]);

        $this->actingAs($this->admin)
            ->post(route('fixtures.store'), fixtureDetails(['team_id' => $this->otherTeam->id]))
            ->assertRedirect(route('fixtures.index'));

        $fixture = Fixture::sole();

        expect($fixture->organisation_id)->toBe($this->club->id);

        Event::assertDispatched(FixtureScheduled::class, fn (FixtureScheduled $event) => $event->fixture instanceof FixtureData
            && $event->fixture->id === $fixture->id
            && $event->fixture->teamName === 'U14 Hawks'
            && $event->fixture->opponent === 'Harbour Town');
    });

    test('scheduling is audited under the module\'s own type', function () {
        $this->actingAs($this->admin)->post(route('fixtures.store'), fixtureDetails(['team_id' => $this->coachedTeam->id]));

        expect(AuditLog::where('subject_type', 'fixtures.fixture')->latest('id')->value('organisation_id'))->toBe($this->club->id);
    });

    test('a coach schedules for a team they coach but not for others', function () {
        $this->actingAs($this->coach);

        $this->post(route('fixtures.store'), fixtureDetails(['team_id' => $this->coachedTeam->id]))->assertRedirect();
        $this->post(route('fixtures.store'), fixtureDetails(['team_id' => $this->otherTeam->id]))->assertForbidden();

        expect(Fixture::count())->toBe(1);
    });

    test('another club\'s team or venue is rejected', function () {
        $foreignTeam = Team::withoutOrganisationScope()->where('organisation_id', $this->otherClub->id)->firstOrFail();
        $foreignVenue = Venue::factory()->for($this->otherClub)->create();

        $this->actingAs($this->admin)
            ->post(route('fixtures.store'), fixtureDetails(['team_id' => $foreignTeam->id, 'venue_id' => $foreignVenue->id]))
            ->assertSessionHasErrors(['team_id', 'venue_id']);
    });

    test('the team cannot be changed once a fixture exists', function () {
        $fixture = Fixture::factory()->for($this->coachedTeam)->create();

        $this->actingAs($this->admin)
            ->put(route('fixtures.update', $fixture), fixtureDetails(['team_id' => $this->otherTeam->id]))
            ->assertSessionHasErrors('team_id');
    });
});

describe('editing', function () {
    test('a coach edits fixtures for their team only', function () {
        $mine = Fixture::factory()->for($this->coachedTeam)->create();
        $theirs = Fixture::factory()->for($this->otherTeam)->create();

        $this->actingAs($this->coach);

        $this->put(route('fixtures.update', $mine), fixtureDetails(['opponent' => 'Renamed FC']))->assertRedirect();
        expect($mine->refresh()->opponent)->toBe('Renamed FC');

        $this->get(route('fixtures.edit', $theirs))->assertForbidden();
        $this->delete(route('fixtures.destroy', $theirs))->assertForbidden();
    });

    test('another club\'s fixture is a 404', function () {
        $this->actingAs($this->admin);

        $this->get(route('fixtures.edit', $this->foreignFixture))->assertNotFound();
        $this->delete(route('fixtures.destroy', $this->foreignFixture))->assertNotFound();
    });
});

describe('venues', function () {
    test('admins manage venues and coaches cannot', function () {
        $this->actingAs($this->admin)->post(route('fixtures.venues.store'), ['name' => 'Riverside Park'])->assertRedirect();
        expect(Venue::sole()->organisation_id)->toBe($this->club->id);

        $this->actingAs($this->coach);
        $this->get(route('fixtures.venues.index'))->assertForbidden();
        $this->post(route('fixtures.venues.store'), ['name' => 'Elsewhere'])->assertForbidden();
    });

    test('removing a venue keeps its fixtures', function () {
        $venue = Venue::factory()->for($this->club)->create();
        $fixture = Fixture::factory()->for($this->coachedTeam)->create(['venue_id' => $venue->id]);

        $this->actingAs($this->admin)->delete(route('fixtures.venues.destroy', $venue))->assertRedirect();

        expect($fixture->refresh()->venue_id)->toBeNull();
    });
});

describe('FixtureLookup contract', function () {
    test('other modules get DTOs for the current club only', function () {
        $fixture = Fixture::factory()->for($this->coachedTeam)->create(['opponent' => 'Lookup FC']);
        Fixture::factory()->for($this->coachedTeam)->played()->create();

        $this->actingAs($this->coach);
        $lookup = app(FixtureLookup::class);

        expect($lookup->find($fixture->id))->toBeInstanceOf(FixtureData::class)
            ->and($lookup->find($fixture->id)->opponent)->toBe('Lookup FC')
            ->and($lookup->find($this->foreignFixture->id))->toBeNull()
            ->and($lookup->forTeam($this->coachedTeam->id))->toHaveCount(2)
            ->and($lookup->upcomingForTeam($this->coachedTeam->id))->toHaveCount(1)
            ->and($lookup->forTeam($this->foreignFixture->team_id))->toBe([]);
    });
});
