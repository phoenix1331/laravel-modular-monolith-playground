<?php

declare(strict_types=1);

use App\Models\User;

test('users land on the area their role works in after logging in', function (string $state, string $route) {
    $user = User::factory()->{$state}()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route($route));

    $this->assertAuthenticatedAs($user);
})->with([
    'worker' => ['worker', 'dashboard'],
    'admin' => ['admin', 'dashboard'],
    'super admin' => ['superAdmin', 'platform.dashboard'],
]);

test('signed-in users visiting the login page are sent to their own area', function (string $state, string $route) {
    $this->actingAs(User::factory()->{$state}()->create())
        ->get(route('login'))
        ->assertRedirect(route($route));
})->with([
    'worker' => ['worker', 'dashboard'],
    'super admin' => ['superAdmin', 'platform.dashboard'],
]);

test('club users can reach the club dashboard but not the platform', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('platform.dashboard'))->assertForbidden();
})->with(['worker', 'admin']);

test('the super admin can reach the platform but not the club dashboard', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(route('platform.dashboard'))->assertOk();
    $this->get(route('dashboard'))->assertForbidden();
});

test('guests are sent to login from both areas', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'platform.dashboard']);

test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});
