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
| Feature tests, and each module's own tests in modules/{Name}/Tests, boot
| the full application against a fresh database. Unit and
| Arch tests run without Laravel so they stay fast and framework-agnostic.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../modules/*/Tests');

/*
|--------------------------------------------------------------------------
| Module test discovery
|--------------------------------------------------------------------------
|
| phpunit.xml globs modules/*\/Tests, so a module still on disk but removed
| from config/modules.php would have its tests run against an app where its
| routes and providers no longer exist. Those tests are skipped instead,
| naming the reason, so uninstalling a module never needs phpunit.xml edited.
|
| The config file is read directly, before Laravel boots: it only returns
| class name strings, and the folder is the second namespace segment
| (Modules\{Name}\{Name}Module lives in modules/{Name}/).
|
| Deleting the module folder removes its tests outright. `make
| uninstall-check MODULE={Name}` does exactly that in a temp copy.
|
*/

$installedModuleFolders = array_map(
    fn (string $definition): string => explode('\\', $definition)[1],
    (require __DIR__.'/../config/modules.php')['installed'],
);

foreach (glob(__DIR__.'/../modules/*/Tests', GLOB_ONLYDIR) ?: [] as $testsFolder) {
    $module = basename(dirname($testsFolder));

    if (in_array($module, $installedModuleFolders, true)) {
        continue;
    }

    pest()->beforeEach(fn () => $this->markTestSkipped("{$module} is not installed in config/modules.php"))
        ->in("../modules/{$module}/Tests");
}

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
