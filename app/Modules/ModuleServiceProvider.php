<?php

declare(strict_types=1);

namespace App\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base class for every module's service provider.
 *
 * Core registers a module's provider only when the module is installed, so
 * everything here (routes, migrations) simply does not exist for an
 * uninstalled module. Extend it and implement `definition()`:
 *
 *     final class FixturesServiceProvider extends ModuleServiceProvider
 *     {
 *         protected function definition(): ModuleDefinition
 *         {
 *             return new FixturesModule;
 *         }
 *     }
 *
 * Conventions it relies on, relative to the module folder:
 * - `Routes/web.php` is loaded inside the club area (signed in, admin or
 *   worker) behind `module:{key}`, so every route 404s for clubs that do
 *   not have the module enabled
 * - `Database/Migrations` is added to the migrator
 * - `Policies/{Model}Policy` is found by Laravel's policy discovery for
 *   `Models/{Model}`, so no policy registration is needed
 *
 * Override `register()` to bind the module's own services, and call
 * `parent::boot()` if you override `boot()`.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function definition(): ModuleDefinition;

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('Database/Migrations'));
        $this->loadModuleRoutes();
    }

    /**
     * Middleware applied to every route in the module's Routes/web.php. Add
     * to this list if a module needs more; keep `module:{key}` in it.
     *
     * @return list<string>
     */
    protected function routeMiddleware(): array
    {
        return ['web', 'auth', 'role:admin,worker', 'module:'.$this->definition()->key()];
    }

    protected function modulePath(string $path = ''): string
    {
        $directory = dirname((string) (new ReflectionClass($this))->getFileName());

        return $path === '' ? $directory : $directory.DIRECTORY_SEPARATOR.$path;
    }

    private function loadModuleRoutes(): void
    {
        $routes = $this->modulePath('Routes/web.php');

        if ($this->app->routesAreCached() || ! is_file($routes)) {
            return;
        }

        Route::middleware($this->routeMiddleware())->group($routes);
    }
}
