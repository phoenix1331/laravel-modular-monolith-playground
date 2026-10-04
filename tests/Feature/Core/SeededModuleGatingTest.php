<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\User;
use App\Modules\ModuleDefinition;
use App\Modules\ModuleRegistry;
use Database\Seeders\ClubSeeder;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Gating across the seeded clubs, for whatever modules are installed.
|
| This Core test never names a module. It reads the installed modules from
| the registry, the wanted modules from ClubSeeder, and the module routes from
| the router (any GET route carrying `module:{key}`). Install or remove a
| module and the expectations follow.
*/

beforeEach(fn () => $this->seed());

/**
 * Parameterless GET routes gated by `module:{key}`, grouped by module key.
 *
 * @return array<string, list<string>>
 */
function moduleGetRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        /** @var RouteDefinition $route */
        if (! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
            continue;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'module:')) {
                $routes[substr($middleware, strlen('module:'))][] = '/'.ltrim($route->uri(), '/');
            }
        }
    }

    return $routes;
}

/**
 * @return list<string>
 */
function expectedModulesFor(array $club): array
{
    return array_values(array_map(
        fn (ModuleDefinition $module) => $module->key(),
        array_filter(
            app(ModuleRegistry::class)->inDependencyOrder(),
            fn (ModuleDefinition $module) => in_array($module->key(), $club['modules'], true),
        ),
    ));
}

test('every installed module has at least one gated page', function () {
    expect(array_keys(moduleGetRoutes()))
        ->toEqualCanonicalizing(array_keys(app(ModuleRegistry::class)->all()));
});

test('each club gets exactly its installed modules, dependencies first', function () {
    foreach (ClubSeeder::CLUBS as $club) {
        $admin = User::where('email', "admin@{$club['domain']}")->sole();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'enabledModules',
                fn ($modules) => collect($modules)->pluck('key')->all() === expectedModulesFor($club),
            ));
    }
});

test('module pages open for clubs that have them and 404 for clubs that do not', function () {
    $routes = moduleGetRoutes();

    foreach (ClubSeeder::CLUBS as $club) {
        $admin = User::where('email', "admin@{$club['domain']}")->sole();
        $enabled = expectedModulesFor($club);

        foreach ($routes as $key => $uris) {
            foreach ($uris as $uri) {
                $status = $this->actingAs($admin)->get($uri)->status();
                $want = in_array($key, $enabled, true) ? 200 : 404;

                expect($status)->toBe($want, "{$club['name']} {$uri}");
            }
        }
    }
});

test('workers are gated the same way', function () {
    $routes = moduleGetRoutes();

    foreach (ClubSeeder::CLUBS as $club) {
        $worker = User::where('email', "coach1@{$club['domain']}")->sole();
        $enabled = expectedModulesFor($club);

        foreach ($routes as $key => $uris) {
            foreach ($uris as $uri) {
                $status = $this->actingAs($worker)->get($uri)->status();

                in_array($key, $enabled, true)
                    ? expect($status)->not->toBe(404, "{$club['name']} {$uri}")
                    : expect($status)->toBe(404, "{$club['name']} {$uri}");
            }
        }
    }
});

test('the super admin is kept out of every module page by role', function () {
    $this->actingAs(User::where('email', 'super@platform.test')->sole());

    foreach (moduleGetRoutes() as $uris) {
        foreach ($uris as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }
});

test('seeded flags obey the dependency rules', function () {
    $registry = app(ModuleRegistry::class);

    Organisation::all()->each(function (Organisation $club) use ($registry) {
        $this->actingAs(User::where('organisation_id', $club->id)->where('role', 'admin')->sole());
        $enabled = collect($this->get(route('dashboard'))->inertiaProps('enabledModules'))->pluck('key');

        $enabled->each(fn (string $key) => expect($enabled->intersect($registry->get($key)->hardDependencies())->count())
            ->toBe(count($registry->get($key)->hardDependencies()), "{$club->name} {$key}"));
    });
});
