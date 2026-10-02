<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Exceptions\InvalidUserOrganisation;
use App\Models\Organisation;
use App\Models\User;

test('workers and admins belong to an organisation', function (Role $role) {
    $organisation = Organisation::factory()->create();

    $user = User::factory()->state(['role' => $role])->forOrganisation($organisation)->create();

    expect($user->organisation->is($organisation))->toBeTrue()
        ->and($user->role)->toBe($role);
})->with([Role::Worker, Role::Admin]);

test('a super admin belongs to no organisation', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->organisation_id)->toBeNull()
        ->and($user->isSuperAdmin())->toBeTrue();
});

test('a worker or admin cannot be saved without an organisation', function (Role $role) {
    User::factory()->state(['role' => $role, 'organisation_id' => null])->create();
})->with([Role::Worker, Role::Admin])->throws(InvalidUserOrganisation::class);

test('a super admin cannot be saved with an organisation', function () {
    User::factory()->state(['role' => Role::SuperAdmin])->create();
})->throws(InvalidUserOrganisation::class);

test('role and organisation are not mass assignable', function () {
    $user = User::factory()->create();
    $originalOrganisation = $user->organisation_id;

    $user->fill(['role' => 'super_admin', 'organisation_id' => null])->save();

    expect($user->refresh()->role)->toBe(Role::Worker)
        ->and($user->organisation_id)->toBe($originalOrganisation);
});

test('an organisation lists its users', function () {
    $organisation = Organisation::factory()->create();
    User::factory()->count(2)->forOrganisation($organisation)->create();
    User::factory()->create();

    expect($organisation->users)->toHaveCount(2);
});
