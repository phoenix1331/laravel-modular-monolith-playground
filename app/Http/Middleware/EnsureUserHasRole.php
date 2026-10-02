<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to one or more roles, e.g. `role:super_admin` or
 * `role:admin,worker`.
 *
 * This is a coarse gate for whole areas (platform vs club). Fine-grained
 * rules, such as "admins manage players, workers only read them", belong in
 * policies.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(Role::from(...), $roles);

        abort_unless(in_array($request->user()?->role, $allowed, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
