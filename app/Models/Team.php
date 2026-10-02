<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Tenancy\BelongsToOrganisation;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A squad within a club, e.g. "Under 12s".
 *
 * @property int $id
 * @property int $organisation_id
 * @property string $name
 * @property string|null $age_group
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'age_group'])]
#[ObservedBy(AuditObserver::class)]
class Team extends Model implements Auditable
{
    use BelongsToOrganisation;

    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Workers (coaches and team managers) assigned to this team.
     *
     * @return BelongsToMany<User, $this>
     */
    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function auditType(): string
    {
        return 'team';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
