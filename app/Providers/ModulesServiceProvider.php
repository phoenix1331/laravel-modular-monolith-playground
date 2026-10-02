<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Installs modules: reads config/modules.php, validates the dependency graph
 * and registers each installed module's service provider, dependencies first.
 * Then defines one Pennant feature flag per installed module, off by default.
 *
 * This is the only place Core touches modules, and it does so through class
 * names read from config, never through imports. Removing a line from
 * config/modules.php uninstalls that module without changing any Core code.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ModuleRegistry::class,
            fn () => ModuleRegistry::fromClasses(config('modules.installed', [])),
        );

        foreach ($this->app->make(ModuleRegistry::class)->inDependencyOrder() as $module) {
            $this->app->register($module->serviceProvider());
        }
    }

    public function boot(ModuleManager $modules): void
    {
        $modules->defineFeatures();
    }
}
