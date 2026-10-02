<?php

declare(strict_types=1);

namespace App\Audit;

/**
 * What happened. Generic verbs only: the *thing* it happened to is a string
 * returned by Auditable::auditType(), so modules can audit their own models
 * without adding cases to a Core enum.
 */
enum AuditAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Login = 'login';
    case Logout = 'logout';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Created',
            self::Update => 'Updated',
            self::Delete => 'Deleted',
            self::Login => 'Logged in',
            self::Logout => 'Logged out',
        };
    }
}
