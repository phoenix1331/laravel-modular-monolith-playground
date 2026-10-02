<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `module:{key}`: the route exists only for clubs that have the module enabled.
 *
 * Returns 404, not 403. A disabled module should look exactly like a module
 * that was never built: no hint that the page exists, and no difference
 * between "your club has not bought this" and "this is not a real URL".
 *
 * Every module route gets this automatically through
 * ModuleServiceProvider::routeMiddleware(), so a module cannot forget it.
 */
class EnsureModuleEnabled
{
    public function __construct(
        private readonly ModuleManager $modules,
        private readonly CurrentOrganisation $organisation,
    ) {}

    public function handle(Request $request, Closure $next, string $key): Response
    {
        $organisation = $this->organisation->get();

        abort_if($organisation === null || ! $this->modules->isEnabled($organisation, $key), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
