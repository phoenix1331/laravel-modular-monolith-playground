<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    $this->otherClub = Organisation::factory()->create();

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->coachedTeam = Team::factory()->for($this->club)->create(['name' => 'U12 Lions']);
    $this->coachedTeam->workers()->attach($this->coach);
    $this->otherTeam = Team::factory()->for($this->club)->create(['name' => 'U14 Hawks']);

    $this->foreignTeam = Team::factory()->for($this->otherClub)->create(['name' => 'Foreign FC']);
});

test('an admin sees every team in their club and can create', function () {
    $this->actingAs($this->admin)
        ->get(route('teams.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('teams/Index')
            ->where('teams.0.name', 'U12 Lions')
            ->where('teams.1.name', 'U14 Hawks')
            ->has('teams', 2)
            ->where('can.create', true));
});

test('a worker sees only the teams they coach and cannot create', function () {
    $this->actingAs($this->coach)
        ->get(route('teams.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('teams', 1)
            ->where('teams.0.name', 'U12 Lions')
            ->where('can.create', false));
});

test('a worker can view a team they coach but not one they do not', function () {
    $this->actingAs($this->coach);

    $this->get(route('teams.show', $this->coachedTeam))->assertOk();
    $this->get(route('teams.show', $this->otherTeam))->assertForbidden();
});

test('another club\'s team is a 404, not a 403', function (string $user) {
    $this->actingAs($this->{$user})
        ->get(route('teams.show', $this->foreignTeam))
        ->assertNotFound();
})->with(['admin', 'coach']);

test('an admin can create a team with coaches', function () {
    $this->actingAs($this->admin)
        ->post(route('teams.store'), [
            'name' => 'U9 Otters',
            'age_group' => 'U9',
            'worker_ids' => [$this->coach->id],
        ])
        ->assertRedirect();

    $team = Team::where('name', 'U9 Otters')->sole();

    expect($team->organisation_id)->toBe($this->club->id)
        ->and($team->workers->pluck('id')->all())->toBe([$this->coach->id]);
});

test('a coach from another club cannot be assigned to a team', function () {
    $foreignCoach = User::factory()->worker()->forOrganisation($this->otherClub)->create();

    $this->actingAs($this->admin)
        ->post(route('teams.store'), ['name' => 'U9 Otters', 'worker_ids' => [$foreignCoach->id]])
        ->assertSessionHasErrors('worker_ids.0');
});

test('team names are unique within a club but may repeat across clubs', function () {
    $this->actingAs($this->admin);

    $this->post(route('teams.store'), ['name' => 'U12 Lions'])->assertSessionHasErrors('name');
    $this->post(route('teams.store'), ['name' => 'Foreign FC'])->assertSessionHasNoErrors();
});

test('an admin can update and delete a team', function () {
    $this->actingAs($this->admin);

    $this->put(route('teams.update', $this->otherTeam), ['name' => 'U14 Falcons'])->assertRedirect();
    expect($this->otherTeam->refresh()->name)->toBe('U14 Falcons');

    $this->delete(route('teams.destroy', $this->otherTeam))->assertRedirect(route('teams.index'));
    expect(Team::find($this->otherTeam->id))->toBeNull();
});

test('a worker cannot change teams', function () {
    $this->actingAs($this->coach);

    $this->get(route('teams.create'))->assertForbidden();
    $this->post(route('teams.store'), ['name' => 'New'])->assertForbidden();
    $this->get(route('teams.edit', $this->coachedTeam))->assertForbidden();
    $this->put(route('teams.update', $this->coachedTeam), ['name' => 'Renamed'])->assertForbidden();
    $this->delete(route('teams.destroy', $this->coachedTeam))->assertForbidden();
});

test('the super admin cannot use club team pages', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('teams.index'))
        ->assertForbidden();
});
