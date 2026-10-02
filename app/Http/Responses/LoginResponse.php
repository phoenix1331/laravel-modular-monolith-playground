<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends each user to the area their role works in after logging in: the
 * platform dashboard for the super admin, the club dashboard for everyone
 * else. Replaces Fortify's single `home` path, which cannot vary by role.
 */
class LoginResponse implements LoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', Response::HTTP_NO_CONTENT);
        }

        /** @var User $user */
        $user = $request->user();

        return redirect()->intended($user->homeUrl());
    }
}
