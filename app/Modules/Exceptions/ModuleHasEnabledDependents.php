<?php

declare(strict_types=1);

namespace App\Modules\Exceptions;

use App\Modules\ModuleDefinition;
use DomainException;

/**
 * Disabling a module that other enabled modules hard-depend on.
 */
final class ModuleHasEnabledDependents extends DomainException
{
    /**
     * @param  list<ModuleDefinition>  $dependents
     */
    public function __construct(public readonly ModuleDefinition $module, public readonly array $dependents)
    {
        parent::__construct(sprintf(
            'Disable %s before disabling %s.',
            implode(', ', array_map(fn (ModuleDefinition $dependent) => $dependent->name(), $dependents)),
            $module->name(),
        ));
    }
}
