<?php

declare(strict_types=1);

namespace Modules\Availability\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Models\Player;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One player's answer for one fixture. Internal to Availability.
 *
 * There is no `fixture()` relation: the fixture belongs to another module,
 * so it is fetched as FixtureData through FixtureLookup, never as a model.
 *
 * @property int $id
 * @property int $organisation_id
 * @property int $fixture_id
 * @property int $player_id
 * @property AvailabilityStatus $status
 * @property string|null $note
 * @property int|null $recorded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('availability_responses')]
#[Fillable(['fixture_id', 'player_id', 'status', 'note', 'recorded_by'])]
#[ObservedBy(AuditObserver::class)]
class AvailabilityResponse extends Model implements Auditable
{
    use BelongsToOrganisation;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => AvailabilityStatus::class];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function auditType(): string
    {
        return 'availability.response';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update];
    }
}
