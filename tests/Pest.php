<?php

declare(strict_types=1);

use App\Modules\ModuleRegistry;
use App\Providers\ModulesServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the full application against a fresh database. Unit and
| Arch tests run without Laravel so they stay fast and framework-agnostic.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Re-run module installation as if the app had booted with these classes in
 * config/modules.php: rebuilds the registry, registers providers and routes.
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
