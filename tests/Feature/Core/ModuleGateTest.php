<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fixtures\Modules\Demo\DemoModule;

beforeEach(function () {
    installModules([DemoModule::class]);

    $this->withModule = Organisation::factory()->create();
    $this->withoutModule = Organisation::factory()->create();
    app(ModuleManager::class)->enable($this->withModule, 'demo');
});

test('a club with the module enabled reaches its routes', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->forOrganisation($this->withModule)->create())
        ->get('/demo')
        ->assertOk();
})->with(['admin', 'worker']);

test('a club without the module gets a 404, as if it did not exist', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->forOrganisation($this->withoutModule)->create())
        ->get('/demo')
        ->assertNotFound();
})->with(['admin', 'worker']);

test('disabling a module hides its routes immediately', function () {
    $user = User::factory()->worker()->forOrganisation($this->withModule)->create();
    app(ModuleManager::class)->disable($this->withModule, 'demo');

    $this->actingAs($user)->get('/demo')->assertNotFound();
});

test('a route gated on a module that is not installed is a 404', function () {
    Route::middleware(['web', 'auth', 'module:not-installed'])->get('/gated', fn () => 'nope');

    $this->actingAs(User::factory()->admin()->forOrganisation($this->withModule)->create())
        ->get('/gated')
        ->assertNotFound();
});

test('club users receive their club\'s enabled modules on every page', function () {
    $this->actingAs(User::factory()->worker()->forOrganisation($this->withModule)->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('enabledModules', [['key' => 'demo', 'name' => 'Demo']]));

    $this->actingAs(User::factory()->worker()->forOrganisation($this->withoutModule)->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('enabledModules', []));
});

test('the super admin has no club, so no enabled modules', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('platform.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('enabledModules', []));
});
