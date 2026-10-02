<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'home' => $request->user()?->homeUrl(),
            ],
            // Modules enabled for the signed-in user's club. The frontend builds
            // navigation from this, so a disabled module never appears
            'enabledModules' => fn () => $this->enabledModules(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return list<array{key: string, name: string}>
     */
    private function enabledModules(): array
    {
        $organisation = app(CurrentOrganisation::class)->get();

        if ($organisation === null) {
            return [];
        }

        return array_map(
            fn (ModuleDefinition $module) => ['key' => $module->key(), 'name' => $module->name()],
            app(ModuleManager::class)->enabledFor($organisation),
        );
    }
}
