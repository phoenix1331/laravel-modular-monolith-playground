<?php

declare(strict_types=1);

namespace Modules\Availability;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;

/**
 * Nothing to bind: Availability consumes Fixtures' public FixtureLookup and
 * offers no contract of its own yet. Routes and migrations come from the
 * base class.
 */
final class AvailabilityServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new AvailabilityModule;
    }
}
