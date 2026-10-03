<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'module' => EnsureModuleEnabled::class,
        ]);

        // Role and module gates run before route model binding. Otherwise a
        // bound model (e.g. {team}) is looked up first: the wrong role gets a
        // 404 from the tenant scope instead of a 403, and a disabled module
        // still queries its own tables before answering 404
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsureUserHasRole::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsureModuleEnabled::class);

        // Signed-in users visiting a guest page (e.g. /login) go to their own area
        $middleware->redirectUsersTo(function (Request $request): string {
            /** @var User $user */
            $user = $request->user();

            return $user->homeUrl();
        });

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
