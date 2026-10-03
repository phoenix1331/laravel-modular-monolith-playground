<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Role access matrix
|--------------------------------------------------------------------------
|
| Every Core page, every role, against the seeded data. One row per route;
| the columns are the expected status for a guest, a worker, an admin and
| the super admin. "login" means redirected to the login page.
|
| A new Core route should get a row here. Module routes are covered by each
| module's own tests plus the module gate.
|
*/

beforeEach(function () {
    $this->seed();

    $this->users = [
        'worker' => User::where('email', 'coach1@riverside.test')->sole(),
        'admin' => User::where('email', 'admin@riverside.test')->sole(),
        'super admin' => User::where('email', 'super@platform.test')->sole(),
    ];

    // A Riverside team the worker coaches, so "view" is allowed for them
    $this->team = Team::withoutOrganisationScope()
        ->whereHas('workers', fn ($workers) => $workers->whereKey($this->users['worker']->id))
        ->firstOrFail();
});

dataset('routes', [
    //                                guest     worker  admin  super admin
    'club dashboard' => [fn () => route('dashboard'), ['login', 200, 200, 403]],
    'teams index' => [fn () => route('teams.index'), ['login', 200, 200, 403]],
    'team page' => [fn () => route('teams.show', test()->team), ['login', 200, 200, 403]],
    'create team' => [fn () => route('teams.create'), ['login', 403, 200, 403]],
    'edit team' => [fn () => route('teams.edit', test()->team), ['login', 403, 200, 403]],
    'add player' => [fn () => route('teams.players.create', test()->team), ['login', 403, 200, 403]],
    'club audit log' => [fn () => route('audit.index'), ['login', 403, 200, 403]],
    'platform dashboard' => [fn () => route('platform.dashboard'), ['login', 403, 403, 200]],
    'platform organisation' => [fn () => route('platform.organisations.show', 1), ['login', 403, 403, 200]],
    'platform audit log' => [fn () => route('platform.audit.index'), ['login', 403, 403, 200]],
    'profile settings' => [fn () => route('profile.edit'), ['login', 200, 200, 200]],
]);

test('access matches the matrix', function (Closure $url, array $expected) {
    $actors = ['guest', 'worker', 'admin', 'super admin'];

    foreach ($actors as $index => $actor) {
        auth()->logout();

        if ($actor !== 'guest') {
            $this->actingAs($this->users[$actor]);
        }

        $response = $this->get($url());
        $want = $expected[$index];

        $want === 'login'
            ? $response->assertRedirect(route('login'))
            : $response->assertStatus($want);
    }
})->with('routes');
