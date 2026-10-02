<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use Carbon\CarbonImmutable;
use Database\Factories\OrganisationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

/**
 * A tenant: one grassroots club.
 *
 * Everything a club owns carries an organisation_id, and module feature flags
 * are scoped to this model, so "enabled" always means "enabled for this club".
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'slug'])]
#[ObservedBy(AuditObserver::class)]
class Organisation extends Model implements Auditable, FeatureScopeSerializeable
{
    /** @use HasFactory<OrganisationFactory> */
    use HasFactory;

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * How Pennant stores this organisation in the features table scope
     * column: `organisation|1` rather than the full class name, so rows stay
     * readable and survive a class rename.
     */
    public function featureScopeSerialize(): string
    {
        return 'organisation|'.$this->getKey();
    }

    public function auditType(): string
    {
        return 'organisation';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
