<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Thrown when a write would put a tenant-owned record in the wrong organisation.
 */
final class CrossOrganisationWrite extends LogicException
{
    public static function noOrganisation(Model $model): self
    {
        return new self(sprintf(
            'Cannot create [%s] without an organisation. Sign in as a club user or call CurrentOrganisation::set() first.',
            $model::class,
        ));
    }

    public static function otherOrganisation(Model $model): self
    {
        return new self(sprintf('Cannot create [%s] for another organisation.', $model::class));
    }

    public static function moved(Model $model): self
    {
        return new self(sprintf('Cannot move [%s] to another organisation.', $model::class));
    }
}
