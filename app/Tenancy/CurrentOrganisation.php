<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * Answers "which organisation is this code running for?".
 *
 * In a web request the answer comes from the signed-in user. Queued jobs,
 * console commands and seeders have no user, so they call `set()` with the
 * organisation they are working on. The super admin has no organisation, so
 * for them this returns null and the scope returns nothing until they bypass
 * it on purpose.
 *
 * Bound as a scoped singleton: one instance per request or job, so an
 * organisation set by one job never leaks into the next.
 */
class CurrentOrganisation
{
    private ?Organisation $override = null;

    public function __construct(private readonly AuthFactory $auth) {}

    public function get(): ?Organisation
    {
        if ($this->override !== null) {
            return $this->override;
        }

        $user = $this->auth->guard()->user();

        return $user instanceof User ? $user->organisation : null;
    }

    public function id(): ?int
    {
        return $this->get()?->id;
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    /**
     * Run as a specific organisation, for code with no signed-in user.
     */
    public function set(Organisation $organisation): void
    {
        $this->override = $organisation;
    }

    public function clear(): void
    {
        $this->override = null;
    }

    /**
     * Run a callback as the given organisation, then restore the previous one.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function runAs(Organisation $organisation, callable $callback): mixed
    {
        $previous = $this->override;
        $this->override = $organisation;

        try {
            return $callback();
        } finally {
            $this->override = $previous;
        }
    }
}
