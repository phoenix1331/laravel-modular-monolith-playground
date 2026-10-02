<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed());

test('the four clubs from the brief are seeded', function () {
    expect(Organisation::orderBy('id')->pluck('name')->all())->toBe([
        'Riverside Juniors FC',
        'Dockside Rovers',
        'Hillcrest Ladies',
        'Valley Vets Walking Football',
    ]);
});

test('each club has one admin and two to three workers', function () {
    Organisation::all()->each(function (Organisation $club) {
        expect($club->users()->where('role', Role::Admin)->count())->toBe(1)
            ->and($club->users()->where('role', Role::Worker)->count())->toBeBetween(2, 3);
    });
});

test('there is one super admin with no organisation', function () {
    $superAdmins = User::where('role', Role::SuperAdmin)->get();

    expect($superAdmins)->toHaveCount(1)
        ->and($superAdmins->first()->organisation_id)->toBeNull()
        ->and($superAdmins->first()->email)->toBe('super@platform.test');
});

test('seeded data obeys the tenancy rules even though seeders skip model events', function () {
    $teams = Team::withoutOrganisationScope()->get();
    $players = Player::withoutOrganisationScope()->with('team')->get();

    expect($teams)->not->toBeEmpty()
        ->and($players)->not->toBeEmpty();

    $players->each(fn (Player $player) => expect($player->organisation_id)->toBe(
        Team::withoutOrganisationScope()->findOrFail($player->team_id)->organisation_id,
    ));

    $teams->each(fn (Team $team) => $team->workers->each(
        fn (User $coach) => expect($coach->organisation_id)->toBe($team->organisation_id)
            ->and($coach->role)->toBe(Role::Worker),
    ));
});

test('every seeded club user logs in with the documented password and lands in their club', function () {
    User::where('role', '!=', Role::SuperAdmin)->get()->each(function (User $user) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->post(route('logout'));
    });
});

test('the super admin logs in to the platform and sees every club', function () {
    $this->post(route('login.store'), ['email' => 'super@platform.test', 'password' => 'password'])
        ->assertRedirect(route('platform.dashboard'));

    $this->get(route('platform.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('organisations', 4));
});

test('each seeded user sees only their own club\'s teams', function () {
    $riversideAdmin = User::where('email', 'admin@riverside.test')->sole();
    $docksideCoach = User::where('email', 'coach1@dockside.test')->sole();

    $this->actingAs($riversideAdmin)
        ->get(route('teams.index'))
        ->assertInertia(fn (Assert $page) => $page->where('teams', fn ($teams) => collect($teams)->pluck('name')->sort()->values()->all() === ['U11 Badgers', 'U13 Hawks', 'U15 Wolves', 'U9 Foxes']));

    $this->actingAs($docksideCoach)
        ->get(route('teams.index'))
        ->assertInertia(fn (Assert $page) => $page->has('teams', 1)->where('teams.0.name', 'First Team'));
});
