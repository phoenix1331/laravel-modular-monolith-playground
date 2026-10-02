<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Modules\Exceptions\ModuleDependencyNotEnabled;
use App\Modules\Exceptions\ModuleHasEnabledDependents;
use App\Modules\Exceptions\UnknownModule;
use App\Modules\ModuleManager;
use App\Modules\ModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Switches one module on or off for one club. All dependency rules live in
 * ModuleManager; this controller only translates its exceptions into a
 * message on the page.
 */
class OrganisationModuleController extends Controller
{
    public function update(Request $request, Organisation $organisation, string $module, ModuleManager $modules, ModuleRegistry $registry): RedirectResponse
    {
        Gate::authorize('manageModules', $organisation);

        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];

        try {
            $enabled
                ? $modules->enable($organisation, $module)
                : $modules->disable($organisation, $module);
        } catch (UnknownModule) {
            abort(404);
        } catch (ModuleDependencyNotEnabled|ModuleHasEnabledDependents $blocked) {
            throw ValidationException::withMessages(['module' => $blocked->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':module :state for :organisation.', [
                'module' => $registry->get($module)->name(),
                'state' => $enabled ? 'enabled' : 'disabled',
                'organisation' => $organisation->name,
            ]),
        ]);

        return back();
    }
}
