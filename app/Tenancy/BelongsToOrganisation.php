<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Makes a model tenant-owned: one trait, and the model can no longer be read
 * or written across organisations by accident.
 *
 * - Reads: OrganisationScope limits every query to the current organisation.
 * - Creates: organisation_id is filled from CurrentOrganisation. Supplying a
 *   different organisation's id throws.
 * - Updates: organisation_id can never change. Records do not move clubs.
 *
 * Every Core model and every module model that holds club data uses this
 * trait. The table needs an `organisation_id` foreign key.
 *
 * The super admin bypasses the scope explicitly with
 * `Model::withoutOrganisationScope()`, so every cross-organisation read is
 * visible in the code and easy to search for.
 *
 * @mixin Model
 */
trait BelongsToOrganisation
{
    public static function bootBelongsToOrganisation(): void
    {
        static::addGlobalScope(new OrganisationScope);

        static::creating(function (Model $model): void {
            $currentId = app(CurrentOrganisation::class)->id();
            $givenId = $model->getAttribute('organisation_id');

            if ($givenId === null && $currentId === null) {
                throw CrossOrganisationWrite::noOrganisation($model);
            }

            if ($givenId !== null && $currentId !== null && (int) $givenId !== $currentId) {
                throw CrossOrganisationWrite::otherOrganisation($model);
            }

            $model->setAttribute('organisation_id', $givenId ?? $currentId);
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('organisation_id')) {
                throw CrossOrganisationWrite::moved($model);
            }
        });
    }

    /**
     * Query across every organisation. For super admin code paths only.
     *
     * @return Builder<static>
     */
    public static function withoutOrganisationScope(): Builder
    {
        return static::query()->withoutGlobalScope(OrganisationScope::class);
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
