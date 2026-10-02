<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('modules:list')]
#[Description('List installed modules and their dependencies')]
class ListModules extends Command
{
    public function handle(ModuleRegistry $registry): int
    {
        $modules = $registry->inDependencyOrder();

        if ($modules === []) {
            $this->components->info('No modules installed. Core is running on its own.');

            return self::SUCCESS;
        }

        $this->table(
            ['Key', 'Name', 'Hard dependencies', 'Soft dependencies'],
            array_map(fn (ModuleDefinition $module) => [
                $module->key(),
                $module->name(),
                implode(', ', $module->hardDependencies()) ?: '-',
                implode(', ', $module->softDependencies()) ?: '-',
            ], $modules),
        );

        return self::SUCCESS;
    }
}
