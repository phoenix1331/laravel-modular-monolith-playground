<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ModuleRegistry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlatformSeeder::class,
            ClubSeeder::class,
        ]);

        // Module demo data. Core only reads class names from the installed
        // definitions, so an uninstalled module's seeder never runs
        foreach (app(ModuleRegistry::class)->inDependencyOrder() as $module) {
            if ($module->seeder() !== null) {
                $this->call($module->seeder());
            }
        }
    }
}
