<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Modules\ModuleDefinition;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use App\Queries\PlatformOrganisations;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The super admin's view of every club and the modules each one has.
 */
class OrganisationController extends Controller
{
    public function index(PlatformOrganisations $organisations, ModuleManager $modules): Response
    {
        Gate::authorize('viewAny', Organisation::class);

        return Inertia::render('platform/Dashboard', [
            'organisations' => $organisations->query()->get()->map(fn (Organisation $organisation) => [
                'id' => $organisation->id,
                'name' => $organisation->name,
                'users_count' => $organisation->users_count,
                'teams_count' => $organisation->teams_count,
                'enabled_modules' => array_map(
                    fn (ModuleDefinition $module) => $module->name(),
                    $modules->enabledFor($organisation),
                ),
            ]),
        ]);
    }

    /**
     * Every installed module with its state for this club and, when a toggle
     * is blocked, the modules that block it. The page explains a greyed-out
     * switch instead of letting the request fail.
     */
    public function show(Organisation $organisation, ModuleRegistry $registry, ModuleManager $modules): Response
    {
        Gate::authorize('view', $organisation);

        $names = fn (array $definitions) => array_map(fn (ModuleDefinition $module) => $module->name(), $definitions);

        return Inertia::render('platform/organisations/Show', [
            'organisation' => ['id' => $organisation->id, 'name' => $organisation->name],
            'modules' => array_map(fn (ModuleDefinition $module) => [
                'key' => $module->key(),
                'name' => $module->name(),
                'description' => $module->description(),
                'enabled' => $modules->isEnabled($organisation, $module->key()),
                'hard_dependencies' => $names(array_map($registry->get(...), $module->hardDependencies())),
                'soft_dependencies' => $module->softDependencies(),
                'enable_blocked_by' => $names($modules->blockersForEnabling($organisation, $module->key())),
                'disable_blocked_by' => $names($modules->blockersForDisabling($organisation, $module->key())),
            ], $registry->inDependencyOrder()),
        ]);
    }
}
