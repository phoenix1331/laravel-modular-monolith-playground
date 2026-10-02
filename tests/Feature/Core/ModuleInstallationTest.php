<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\ModuleRegistry;
use App\Providers\ModulesServiceProvider;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Modules\Demo\DemoModule;
use Tests\Fixtures\Modules\Demo\DemoServiceProvider;

/**
 * Re-run module installation as if the app booted with the given config.
 *
 * @param  list<class-string>  $installed
 */
function installModules(array $installed): void
{
    config(['modules.installed' => $installed]);
    app()->forgetInstance(ModuleRegistry::class);
    (new ModulesServiceProvider(app()))->register();
    Route::getRoutes()->refreshNameLookups();
}

test('with nothing installed, no module provider or route exists', function () {
    expect(app(ModuleRegistry::class)->all())->toBe([])
        ->and(app()->getProvider(DemoServiceProvider::class))->toBeNull()
        ->and(Route::has('demo.index'))->toBeFalse();
});

test('installing a module registers its provider and routes', function () {
    installModules([DemoModule::class]);

    expect(app(ModuleRegistry::class)->has('demo'))->toBeTrue()
        ->and(app()->getProvider(DemoServiceProvider::class))->not->toBeNull()
        ->and(Route::has('demo.index'))->toBeTrue();
});

test('module routes sit inside the club area', function () {
    installModules([DemoModule::class]);

    $this->get('/demo')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->worker()->create())->get('/demo')->assertOk()->assertSee('demo module');
    $this->actingAs(User::factory()->superAdmin()->create())->get('/demo')->assertForbidden();
});

test('modules:list shows installed modules', function () {
    installModules([DemoModule::class]);

    $this->artisan('modules:list')->expectsOutputToContain('demo')->assertSuccessful();
});
