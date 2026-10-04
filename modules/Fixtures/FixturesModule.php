<?php

declare(strict_types=1);

namespace Modules\Fixtures;

use App\Modules\ModuleDefinition;
use Modules\Fixtures\Database\Seeders\FixturesSeeder;

/**
 * Fixtures: matches, venues and kick-off times. The reference module: every
 * other module copies its shape.
 *
 * Installed by listing this class in config/modules.php. No dependencies,
 * so any club can have it on its own. Availability and MatchReports
 * hard-depend on it by key ('fixtures'), never by class.
 */
final class FixturesModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'fixtures';
    }

    public function name(): string
    {
        return 'Fixtures';
    }

    public function description(): string
    {
        return 'Matches, venues and kick-off times for each team.';
    }

    public function serviceProvider(): string
    {
        return FixturesServiceProvider::class;
    }

    public function seeder(): string
    {
        return FixturesSeeder::class;
    }
}
