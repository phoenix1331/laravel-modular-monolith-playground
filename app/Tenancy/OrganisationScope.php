<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Adds `where organisation_id = ?` to every query on a tenant-owned model.
 *
 * Fails closed: with no current organisation (a guest, a console command that
 * has not called CurrentOrganisation::set(), or the super admin) the query
 * matches nothing. Forgetting to set an organisation therefore shows an empty
 * page rather than every club's data.
 *
 * @implements Scope<Model>
 */
class OrganisationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organisationId = app(CurrentOrganisation::class)->id();

        if ($organisationId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('organisation_id'), $organisationId);
    }
}
