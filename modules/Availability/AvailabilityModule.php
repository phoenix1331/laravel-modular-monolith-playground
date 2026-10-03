<?php

declare(strict_types=1);

namespace Modules\Availability;

use App\Modules\ModuleDefinition;

/**
 * Availability: coaches record which players can make each fixture.
 *
 * Hard-depends on Fixtures, by key. It cannot be enabled for a club until
 * Fixtures is, and Fixtures cannot be switched off while this is on.
 * Fixtures itself knows nothing about this module.
 */
final class AvailabilityModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'availability';
    }

    public function name(): string
    {
        return 'Availability';
    }

    public function description(): string
    {
        return 'Coaches mark which players are available for each fixture.';
    }

    public function hardDependencies(): array
    {
        return ['fixtures'];
    }

    public function serviceProvider(): string
    {
        return AvailabilityServiceProvider::class;
    }
}
