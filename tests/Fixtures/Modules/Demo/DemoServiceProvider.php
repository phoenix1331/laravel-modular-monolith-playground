<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Demo;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;

final class DemoServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new DemoModule;
    }
}
