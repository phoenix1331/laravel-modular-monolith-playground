<?php

declare(strict_types=1);

namespace Modules\Compliance;

use App\Modules\ModuleDefinition;
use Modules\Compliance\Database\Seeders\ComplianceSeeder;

/**
 * Compliance: safeguarding and other certificates for coaches, with expiry
 * tracking so admins see who is out of date before match day.
 */
final class ComplianceModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'compliance';
    }

    public function name(): string
    {
        return 'Compliance';
    }

    public function description(): string
    {
        return 'Safeguarding certificates for coaches, with expiry tracking.';
    }

    public function serviceProvider(): string
    {
        return ComplianceServiceProvider::class;
    }

    public function seeder(): string
    {
        return ComplianceSeeder::class;
    }
}
