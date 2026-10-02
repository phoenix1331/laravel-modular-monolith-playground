<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The three roles a user can hold.
 *
 * Workers and admins always belong to exactly one organisation. The super
 * admin operates the platform, belongs to none, and is the only role allowed
 * to see across organisations. Players are records, not users, so they have
 * no role here.
 */
enum Role: string
{
    /** Coach or team manager: uses the enabled modules for their teams. */
    case Worker = 'worker';

    /** Club secretary or treasurer: manages users, teams and players for their club. */
    case Admin = 'admin';

    /** Platform operator: sees every organisation and toggles modules per organisation. */
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Worker => 'Worker',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super admin',
        };
    }

    /**
     * Whether users with this role must belong to an organisation.
     */
    public function requiresOrganisation(): bool
    {
        return $this !== self::SuperAdmin;
    }
}
