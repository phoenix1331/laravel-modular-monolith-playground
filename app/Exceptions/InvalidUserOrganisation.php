<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\Role;
use LogicException;

/**
 * Thrown when a user's role and organisation disagree.
 *
 * Tenancy depends on this invariant: every worker and admin has an
 * organisation to be scoped to, and the super admin has none, so there is no
 * organisation that could accidentally narrow what they see.
 */
final class InvalidUserOrganisation extends LogicException
{
    public static function missing(Role $role): self
    {
        return new self("A user with the [{$role->value}] role must belong to an organisation.");
    }

    public static function notAllowed(): self
    {
        return new self('A super admin cannot belong to an organisation.');
    }
}
