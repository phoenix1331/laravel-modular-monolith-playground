<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Demo;

use App\Modules\ModuleDefinition;

/**
 * Smallest possible module, used to prove that installing a module registers
 * its provider and routes.
 */
final class DemoModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'demo';
    }

    public function name(): string
    {
        return 'Demo';
    }

    public function serviceProvider(): string
    {
        return DemoServiceProvider::class;
    }
}
