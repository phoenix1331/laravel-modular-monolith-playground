<?php

declare(strict_types=1);

namespace Tests;

use App\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * For a module test that drives a soft dependency (another module the
     * one under test reacts to but never requires). When that module is
     * uninstalled the scenario cannot happen, so the test is skipped rather
     * than failed. This keeps `make uninstall-check` green for the soft
     * dependency while the module's own standalone tests still run.
     */
    protected function skipUnlessModuleInstalled(string $key): void
    {
        if (! $this->app->make(ModuleRegistry::class)->has($key)) {
            $this->markTestSkipped("Module [{$key}] is not installed.");
        }
    }
}
