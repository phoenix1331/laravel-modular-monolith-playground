<?php

declare(strict_types=1);

namespace Modules\Fixtures\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Fixtures\Database\Factories\VenueFactory;

/**
 * Where a club plays. Internal to Fixtures.
 *
 * Uses the same Core building blocks as Core's own models: tenancy via
 * BelongsToOrganisation, audit via Auditable. Module tables are prefixed with
 * the module key so they never collide with Core or another module.
 *
 * @property int $id
 * @property int $organisation_id
 * @property string $name
 * @property string|null $address
 * @property string|null $postcode
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('fixtures_venues')]
#[Fillable(['name', 'address', 'postcode'])]
#[UseFactory(VenueFactory::class)]
#[ObservedBy(AuditObserver::class)]
class Venue extends Model implements Auditable
{
    use BelongsToOrganisation;

    /** @use HasFactory<VenueFactory> */
    use HasFactory;

    /**
     * @return HasMany<Fixture, $this>
     */
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    public function auditType(): string
    {
        return 'fixtures.venue';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
