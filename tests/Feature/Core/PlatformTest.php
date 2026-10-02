<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fixtures\Modules\FakeModule;

beforeEach(function () {
    app()->instance(ModuleRegistry::class, new ModuleRegistry([
        FakeModule::make('fixtures'),
        FakeModule::make('availability', hard: ['fixtures']),
        FakeModule::make('kit-shop', soft: ['subscriptions']),
        FakeModule::make('subscriptions'),
    ]));

    $this->riverside = Organisation::factory()->create(['name' => 'Riverside Juniors FC']);
    $this->valley = Organisation::factory()->create(['name' => 'Valley Vets Walking Football']);

    User::factory()->admin()->forOrganisation($this->riverside)->create();
    User::factory()->worker()->forOrganisation($this->riverside)->create();
    Team::factory()->count(3)->for($this->riverside)->create();

    $this->superAdmin = User::factory()->superAdmin()->create();
});

function moduleRow(array $modules, string $key): array
{
    return collect($modules)->firstWhere('key', $key);
}

test('the platform dashboard lists every organisation with counts across clubs', function () {
    app(ModuleManager::class)->enable($this->riverside, 'fixtures');

    $this->actingAs($this->superAdmin)
        ->get(route('platform.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/Dashboard')
            ->has('organisations', 2)
            ->where('organisations.0.name', 'Riverside Juniors FC')
            ->where('organisations.0.users_count', 2)
            ->where('organisations.0.teams_count', 3)
            ->where('organisations.0.enabled_modules', ['Fixtures'])
            ->where('organisations.1.enabled_modules', []));
});

test('an organisation page shows each module with its state and blockers', function () {
    app(ModuleManager::class)->enable($this->riverside, 'fixtures');
    app(ModuleManager::class)->enable($this->riverside, 'availability');

    $this->actingAs($this->superAdmin)
        ->get(route('platform.organisations.show', $this->riverside))
        ->assertInertia(function (Assert $page) {
            $modules = $page->toArray()['props']['modules'];

            expect(array_column($modules, 'key'))->toBe(['fixtures', 'availability', 'kit-shop', 'subscriptions'])
                ->and(moduleRow($modules, 'fixtures'))->toMatchArray(['enabled' => true, 'disable_blocked_by' => ['Availability']])
                ->and(moduleRow($modules, 'availability'))->toMatchArray(['enabled' => true, 'hard_dependencies' => ['Fixtures'], 'disable_blocked_by' => []])
                ->and(moduleRow($modules, 'kit-shop'))->toMatchArray(['enabled' => false, 'soft_dependencies' => ['subscriptions'], 'enable_blocked_by' => []]);
        });
});

test('a dependent module shows what must be enabled first', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('platform.organisations.show', $this->valley))
        ->assertInertia(fn (Assert $page) => expect(moduleRow($page->toArray()['props']['modules'], 'availability')['enable_blocked_by'])->toBe(['Fixtures']));
});

test('the super admin can enable and disable a module for a club', function () {
    $this->actingAs($this->superAdmin);
    $url = route('platform.organisations.modules.update', [$this->valley, 'fixtures']);

    $this->put($url, ['enabled' => true])->assertRedirect()->assertSessionHasNoErrors();
    expect(app(ModuleManager::class)->isEnabled($this->valley, 'fixtures'))->toBeTrue()
        ->and(app(ModuleManager::class)->isEnabled($this->riverside, 'fixtures'))->toBeFalse();

    $this->put($url, ['enabled' => false])->assertRedirect()->assertSessionHasNoErrors();
    expect(app(ModuleManager::class)->isEnabled($this->valley, 'fixtures'))->toBeFalse();
});

test('a blocked toggle returns the reason and changes nothing', function () {
    $this->actingAs($this->superAdmin)
        ->from(route('platform.organisations.show', $this->valley))
        ->put(route('platform.organisations.modules.update', [$this->valley, 'availability']), ['enabled' => true])
        ->assertRedirect(route('platform.organisations.show', $this->valley))
        ->assertSessionHasErrors(['module' => 'Enable Fixtures before enabling Availability.']);

    expect(app(ModuleManager::class)->isEnabled($this->valley, 'availability'))->toBeFalse();
});

test('disabling a dependency is blocked while a dependent is on', function () {
    app(ModuleManager::class)->enable($this->riverside, 'fixtures');
    app(ModuleManager::class)->enable($this->riverside, 'availability');

    $this->actingAs($this->superAdmin)
        ->put(route('platform.organisations.modules.update', [$this->riverside, 'fixtures']), ['enabled' => false])
        ->assertSessionHasErrors(['module' => 'Disable Availability before disabling Fixtures.']);

    expect(app(ModuleManager::class)->isEnabled($this->riverside, 'fixtures'))->toBeTrue();
});

test('toggling a module that is not installed is a 404', function () {
    $this->actingAs($this->superAdmin)
        ->put(route('platform.organisations.modules.update', [$this->valley, 'compliance']), ['enabled' => true])
        ->assertNotFound();
});

test('the enabled flag is required', function () {
    $this->actingAs($this->superAdmin)
        ->put(route('platform.organisations.modules.update', [$this->valley, 'fixtures']), [])
        ->assertSessionHasErrors('enabled');
});

test('club users cannot use the platform', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->forOrganisation($this->riverside)->create());

    $this->get(route('platform.dashboard'))->assertForbidden();
    $this->get(route('platform.organisations.show', $this->riverside))->assertForbidden();
    $this->put(route('platform.organisations.modules.update', [$this->riverside, 'fixtures']), ['enabled' => true])->assertForbidden();

    expect(app(ModuleManager::class)->isEnabled($this->riverside, 'fixtures'))->toBeFalse();
})->with(['admin', 'worker']);
