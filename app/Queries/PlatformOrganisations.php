<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Organisation;
use App\Tenancy\OrganisationScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every organisation with headline counts, for the super admin.
 *
 * Team counts go through the Team model, which is tenant-scoped. The super
 * admin has no current organisation, so the scope would match nothing and
 * every club would show zero teams. The bypass below is deliberate and is
 * the kind of line `withoutGlobalScope(OrganisationScope::class)` should
 * only ever appear in: platform code, read-only, explicit.
 */
class PlatformOrganisations
{
    /**
     * @return Builder<Organisation>
     */
    public function query(): Builder
    {
        return Organisation::query()
            ->withCount([
                'users',
                'teams' => fn (Builder $teams) => $teams->withoutGlobalScope(OrganisationScope::class),
            ])
            ->orderBy('name');
    }
}
