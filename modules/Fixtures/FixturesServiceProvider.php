<?php

declare(strict_types=1);

namespace Modules\Fixtures;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;
use Modules\Fixtures\Contracts\FixtureLookup;
use Modules\Fixtures\Lookup\EloquentFixtureLookup;

/**
 * Registered by Core only while Fixtures is installed.
 *
 * The base class loads Routes/web.php (behind `module:fixtures`) and
 * Database/Migrations. This provider adds the one thing other modules may
 * depend on: an implementation of the public FixtureLookup contract.
 */
final class FixturesServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new FixturesModule;
    }

    public function register(): void
    {
        // Other modules type-hint the contract; only this module knows the Eloquent implementation
        $this->app->bind(FixtureLookup::class, EloquentFixtureLookup::class);
    }
}
