<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Role;
use App\Models\User;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Workers in the current club, e.g. to assign to a team.
 *
 * User is not covered by OrganisationScope (see CONTEXT.md), so the
 * organisation filter is applied here explicitly.
 */
class ClubWorkers
{
    public function __construct(private readonly CurrentOrganisation $organisation) {}

    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        return User::query()
            ->where('organisation_id', $this->organisation->id())
            ->where('role', Role::Worker)
            ->orderBy('name');
    }
}
