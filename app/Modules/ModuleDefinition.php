<?php

declare(strict_types=1);

namespace App\Modules;

/**
 * Describes one module to Core: what it is called and what it depends on.
 *
 * Every module ships exactly one definition at `modules/{Name}/{Name}Module.php`
 * and is installed by listing that class in config/modules.php:
 *
 *     final class AvailabilityModule extends ModuleDefinition
 *     {
 *         public function key(): string { return 'availability'; }
 *         public function name(): string { return 'Availability'; }
 *         public function hardDependencies(): array { return ['fixtures']; }
 *         public function serviceProvider(): string { return AvailabilityServiceProvider::class; }
 *     }
 *
 * Dependencies are listed by key, never by class. Writing
 * `FixturesModule::class` here would make Availability import a Fixtures
 * internal, which Deptrac forbids. A key is just a string, so the only thing
 * modules share is a name.
 *
 * Hard dependency: this module cannot work without the other one. It can
 * only be enabled for a club once the dependency is enabled, and the
 * dependency cannot be disabled while this module is on.
 *
 * Soft dependency: this module works without the other one but behaves
 * differently when it is present (e.g. KitShop restricts orders to paid
 * members only when Subscriptions is enabled). Nothing is blocked.
 */
abstract class ModuleDefinition
{
    /**
     * Stable identifier: lowercase letters, digits and hyphens, e.g.
     * `match-reports`. Used for feature flags, the `module:{key}` route
     * middleware, audit types and the frontend folder. Never change it once
     * clubs have the module enabled.
     */
    abstract public function key(): string;

    /**
     * Human-readable name shown in the super admin dashboard and navigation.
     */
    abstract public function name(): string;

    /**
     * The module's service provider. Core registers it only when the module
     * is installed.
     *
     * @return class-string<ModuleServiceProvider>
     */
    abstract public function serviceProvider(): string;

    /**
     * One-line summary for the super admin dashboard.
     */
    public function description(): string
    {
        return '';
    }

    /**
     * Keys of modules that must be installed and enabled for this one to work.
     *
     * @return list<string>
     */
    public function hardDependencies(): array
    {
        return [];
    }

    /**
     * Keys of modules this one integrates with when present.
     *
     * @return list<string>
     */
    public function softDependencies(): array
    {
        return [];
    }
}
