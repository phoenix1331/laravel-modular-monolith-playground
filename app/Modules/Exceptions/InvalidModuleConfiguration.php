<?php

declare(strict_types=1);

namespace App\Modules\Exceptions;

use LogicException;

/**
 * config/modules.php describes a set of modules that cannot work together.
 * Thrown while the application boots, so a broken install fails immediately
 * rather than on the first request that touches the module.
 */
final class InvalidModuleConfiguration extends LogicException
{
    public static function notADefinition(string $class): self
    {
        return new self("[{$class}] in config/modules.php is not a ModuleDefinition.");
    }

    public static function invalidKey(string $key, string $class): self
    {
        return new self("Module key [{$key}] on [{$class}] must be lowercase letters, digits and hyphens.");
    }

    public static function duplicateKey(string $key): self
    {
        return new self("Two installed modules use the key [{$key}].");
    }

    public static function missingHardDependency(string $module, string $dependency): self
    {
        return new self("Module [{$module}] needs [{$dependency}], which is not installed. Install it or remove [{$module}] from config/modules.php.");
    }

    /**
     * @param  list<string>  $path
     */
    public static function cycle(array $path): self
    {
        return new self('Module hard dependencies form a cycle: '.implode(' -> ', $path).'.');
    }
}
