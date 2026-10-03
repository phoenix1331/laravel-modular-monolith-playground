<?php

declare(strict_types=1);

namespace Modules\MatchReports;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;

final class MatchReportsServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new MatchReportsModule;
    }
}
