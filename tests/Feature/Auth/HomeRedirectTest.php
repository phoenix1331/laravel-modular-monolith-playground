<?php

declare(strict_types=1);

use App\Models\User;

test('guests visiting / are sent to the login form', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('signed-in users visiting / are sent to their own area', function (string $state, string $route) {
    $this->actingAs(User::factory()->{$state}()->create())
        ->get('/')
        ->assertRedirect(route($route));
})->with([
    'worker' => ['worker', 'dashboard'],
    'admin' => ['admin', 'dashboard'],
    'super admin' => ['superAdmin', 'platform.dashboard'],
]);
