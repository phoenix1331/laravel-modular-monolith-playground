<?php

declare(strict_types=1);

namespace Modules\Compliance;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;

final class ComplianceServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new ComplianceModule;
    }
}
