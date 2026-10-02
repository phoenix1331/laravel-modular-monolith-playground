<?php

declare(strict_types=1);

namespace App\Modules;

use App\Modules\Exceptions\InvalidModuleConfiguration;
use App\Modules\Exceptions\UnknownModule;

/**
 * The installed modules and the dependency graph between them.
 *
 * "Installed" means listed in config/modules.php. Whether a module is
 * *enabled* for a club is a separate, per-organisation question answered by
 * feature flags, not by this class.
 *
 * The registry validates the whole graph when it is built: unknown classes,
 * duplicate or malformed keys, hard dependencies that are not installed and
 * dependency cycles all throw InvalidModuleConfiguration during boot.
 *
 * Plain PHP with no framework calls, so it is unit tested without booting
 * Laravel.
 */
final class ModuleRegistry
{
    private const string KEY_PATTERN = '/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/';

    /** @var array<string, ModuleDefinition> */
    private array $modules = [];

    /**
     * @param  iterable<ModuleDefinition>  $definitions
     */
    public function __construct(iterable $definitions)
    {
        foreach ($definitions as $definition) {
            $key = $definition->key();

            if (preg_match(self::KEY_PATTERN, $key) !== 1) {
                throw InvalidModuleConfiguration::invalidKey($key, $definition::class);
            }

            if (isset($this->modules[$key])) {
                throw InvalidModuleConfiguration::duplicateKey($key);
            }

            $this->modules[$key] = $definition;
        }

        $this->assertHardDependenciesInstalled();
        $this->assertNoCycles();
    }

    /**
     * Build from the class names listed in config/modules.php.
     *
     * @param  list<string>  $classes
     */
    public static function fromClasses(array $classes): self
    {
        return new self(array_map(function (string $class): ModuleDefinition {
            if (! is_subclass_of($class, ModuleDefinition::class)) {
                throw InvalidModuleConfiguration::notADefinition($class);
            }

            return new $class;
        }, $classes));
    }

    /**
     * @return array<string, ModuleDefinition>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function has(string $key): bool
    {
        return isset($this->modules[$key]);
    }

    public function get(string $key): ModuleDefinition
    {
        return $this->modules[$key] ?? throw UnknownModule::key($key);
    }

    /**
     * Installed modules that list $key as a hard dependency: the ones that
     * would break if $key were switched off.
     *
     * @return list<ModuleDefinition>
     */
    public function hardDependentsOf(string $key): array
    {
        $this->get($key);

        return array_values(array_filter(
            $this->modules,
            fn (ModuleDefinition $module) => in_array($key, $module->hardDependencies(), true),
        ));
    }

    /**
     * Soft dependencies of $key that are installed. Uninstalled soft
     * dependencies are simply absent, which is allowed.
     *
     * @return list<ModuleDefinition>
     */
    public function installedSoftDependenciesOf(string $key): array
    {
        return array_values(array_map(
            $this->get(...),
            array_filter($this->get($key)->softDependencies(), $this->has(...)),
        ));
    }

    /**
     * Every module, dependencies first. Service providers are registered in
     * this order so a module's provider always runs after the providers of
     * the modules it needs.
     *
     * @return list<ModuleDefinition>
     */
    public function inDependencyOrder(): array
    {
        $ordered = [];

        $visit = function (string $key) use (&$visit, &$ordered): void {
            if (isset($ordered[$key])) {
                return;
            }

            foreach ($this->modules[$key]->hardDependencies() as $dependency) {
                $visit($dependency);
            }

            $ordered[$key] = $this->modules[$key];
        };

        foreach (array_keys($this->modules) as $key) {
            $visit($key);
        }

        return array_values($ordered);
    }

    private function assertHardDependenciesInstalled(): void
    {
        foreach ($this->modules as $key => $module) {
            foreach ($module->hardDependencies() as $dependency) {
                if (! $this->has($dependency)) {
                    throw InvalidModuleConfiguration::missingHardDependency($key, $dependency);
                }
            }
        }
    }

    /**
     * Depth-first search over hard dependencies. A module reached again while
     * it is still on the current path closes a cycle.
     */
    private function assertNoCycles(): void
    {
        $finished = [];

        /** @param list<string> $path */
        $visit = function (string $key, array $path) use (&$visit, &$finished): void {
            if (isset($finished[$key])) {
                return;
            }

            if (in_array($key, $path, true)) {
                $start = (int) array_search($key, $path, true);

                throw InvalidModuleConfiguration::cycle([...array_values(array_slice($path, $start)), $key]);
            }

            foreach ($this->modules[$key]->hardDependencies() as $dependency) {
                $visit($dependency, [...$path, $key]);
            }

            $finished[$key] = true;
        };

        foreach (array_keys($this->modules) as $key) {
            $visit($key, []);
        }
    }
}
