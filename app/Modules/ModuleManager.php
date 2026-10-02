<?php

declare(strict_types=1);

namespace App\Modules;

use App\Audit\AuditAction;
use App\Audit\AuditLogger;
use App\Models\Organisation;
use App\Modules\Exceptions\ModuleDependencyNotEnabled;
use App\Modules\Exceptions\ModuleHasEnabledDependents;
use Laravel\Pennant\FeatureManager;

/**
 * Answers and changes "is this module enabled for this club?".
 *
 * Installed comes from the ModuleRegistry (config/modules.php). Enabled is a
 * Pennant feature flag per organisation, stored in the `features` table as
 * `module:{key}` for scope `organisation|{id}`. Toggling a flag never touches
 * the module's own tables, so a club's data survives being switched off and
 * on again.
 *
 * Hard dependency rules are enforced here, in one place, for every caller:
 * - enable() throws ModuleDependencyNotEnabled while any hard dependency is off
 * - disable() throws ModuleHasEnabledDependents while a dependent is still on
 *
 * Every successful toggle is written to the audit log.
 */
final class ModuleManager
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly FeatureManager $features,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * The Pennant feature name for a module.
     */
    public static function feature(string $key): string
    {
        return "module:{$key}";
    }

    /**
     * Define a Pennant feature per installed module, off by default. Clubs
     * get a module only when the super admin enables it for them.
     */
    public function defineFeatures(): void
    {
        foreach ($this->registry->all() as $key => $module) {
            $this->features->define(self::feature($key), fn () => false);
        }
    }

    /**
     * Enabled means: installed, flagged on for this club, and every hard
     * dependency enabled too. The dependency check is redundant while the
     * rules below hold, but it keeps the answer correct even if flags were
     * changed directly in the database.
     */
    public function isEnabled(Organisation $organisation, string $key): bool
    {
        if (! $this->registry->has($key)) {
            return false;
        }

        if (! $this->features->for($organisation)->active(self::feature($key))) {
            return false;
        }

        foreach ($this->registry->get($key)->hardDependencies() as $dependency) {
            if (! $this->isEnabled($organisation, $dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Modules enabled for the club, dependencies first.
     *
     * @return list<ModuleDefinition>
     */
    public function enabledFor(Organisation $organisation): array
    {
        return array_values(array_filter(
            $this->registry->inDependencyOrder(),
            fn (ModuleDefinition $module) => $this->isEnabled($organisation, $module->key()),
        ));
    }

    /**
     * Hard dependencies that must be enabled first. Empty means it can be enabled.
     *
     * @return list<ModuleDefinition>
     */
    public function blockersForEnabling(Organisation $organisation, string $key): array
    {
        return array_values(array_map(
            $this->registry->get(...),
            array_filter(
                $this->registry->get($key)->hardDependencies(),
                fn (string $dependency) => ! $this->isEnabled($organisation, $dependency),
            ),
        ));
    }

    /**
     * Enabled modules that must be disabled first. Empty means it can be disabled.
     *
     * @return list<ModuleDefinition>
     */
    public function blockersForDisabling(Organisation $organisation, string $key): array
    {
        return array_values(array_filter(
            $this->registry->hardDependentsOf($key),
            fn (ModuleDefinition $dependent) => $this->isEnabled($organisation, $dependent->key()),
        ));
    }

    public function enable(Organisation $organisation, string $key): void
    {
        $module = $this->registry->get($key);

        if ($this->isEnabled($organisation, $key)) {
            return;
        }

        $missing = $this->blockersForEnabling($organisation, $key);

        if ($missing !== []) {
            throw new ModuleDependencyNotEnabled($module, $missing);
        }

        $this->features->for($organisation)->activate(self::feature($key));
        $this->record(AuditAction::Enable, $organisation, $module);
    }

    public function disable(Organisation $organisation, string $key): void
    {
        $module = $this->registry->get($key);

        if (! $this->features->for($organisation)->active(self::feature($key))) {
            return;
        }

        $dependents = $this->blockersForDisabling($organisation, $key);

        if ($dependents !== []) {
            throw new ModuleHasEnabledDependents($module, $dependents);
        }

        $this->features->for($organisation)->deactivate(self::feature($key));
        $this->record(AuditAction::Disable, $organisation, $module);
    }

    private function record(AuditAction $action, Organisation $organisation, ModuleDefinition $module): void
    {
        $this->audit->log(
            $action,
            'module',
            $module->key(),
            ['module' => $module->name()],
            $organisation->id,
        );
    }
}
