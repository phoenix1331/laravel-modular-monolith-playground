<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->team = Team::factory()->for($this->club)->create();
    $this->team->workers()->attach($this->coach);
    $this->player = Player::factory()->for($this->team)->create(['first_name' => 'Sam', 'last_name' => 'Jones']);

    $this->foreignPlayer = Player::factory()->create();
});

test('players are listed on their team page', function () {
    $this->actingAs($this->coach)
        ->get(route('teams.show', $this->team))
        ->assertInertia(fn (Assert $page) => $page
            ->has('players', 1)
            ->where('players.0.full_name', 'Sam Jones')
            ->where('can.managePlayers', false));
});

test('an admin can add, edit and remove a player', function () {
    $this->actingAs($this->admin);

    $this->post(route('teams.players.store', $this->team), [
        'first_name' => 'Alex',
        'last_name' => 'Smith',
        'shirt_number' => 7,
    ])->assertRedirect(route('teams.show', $this->team));

    $alex = Player::where('first_name', 'Alex')->sole();
    expect($alex->team_id)->toBe($this->team->id)
        ->and($alex->organisation_id)->toBe($this->club->id);

    $this->put(route('players.update', $alex), ['first_name' => 'Alex', 'last_name' => 'Brown'])->assertRedirect();
    expect($alex->refresh()->last_name)->toBe('Brown');

    $this->delete(route('players.destroy', $alex))->assertRedirect();
    expect(Player::find($alex->id))->toBeNull();
});

test('a worker cannot change players', function () {
    $this->actingAs($this->coach);

    $this->get(route('teams.players.create', $this->team))->assertForbidden();
    $this->post(route('teams.players.store', $this->team), ['first_name' => 'A', 'last_name' => 'B'])->assertForbidden();
    $this->get(route('players.edit', $this->player))->assertForbidden();
    $this->put(route('players.update', $this->player), ['first_name' => 'A', 'last_name' => 'B'])->assertForbidden();
    $this->delete(route('players.destroy', $this->player))->assertForbidden();
});

test('another club\'s player is a 404', function () {
    $this->actingAs($this->admin);

    $this->get(route('players.edit', $this->foreignPlayer))->assertNotFound();
    $this->delete(route('players.destroy', $this->foreignPlayer))->assertNotFound();
});

test('player details are validated', function () {
    $this->actingAs($this->admin)
        ->post(route('teams.players.store', $this->team), [
            'first_name' => '',
            'last_name' => 'Smith',
            'date_of_birth' => now()->addDay()->toDateString(),
            'shirt_number' => 150,
        ])
        ->assertSessionHasErrors(['first_name', 'date_of_birth', 'shirt_number']);
});
