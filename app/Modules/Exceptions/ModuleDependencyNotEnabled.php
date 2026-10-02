<?php

declare(strict_types=1);

namespace App\Modules\Exceptions;

use App\Modules\ModuleDefinition;
use DomainException;

/**
 * Enabling a module whose hard dependencies are not enabled for the club.
 */
final class ModuleDependencyNotEnabled extends DomainException
{
    /**
     * @param  list<ModuleDefinition>  $missing
     */
    public function __construct(public readonly ModuleDefinition $module, public readonly array $missing)
    {
        parent::__construct(sprintf(
            'Enable %s before enabling %s.',
            implode(', ', array_map(fn (ModuleDefinition $dependency) => $dependency->name(), $missing)),
            $module->name(),
        ));
    }
}
