<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Models\Player;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Subscriptions\Contracts\OverduePayment;
use Modules\Subscriptions\Database\Factories\FeeFactory;

/**
 * One amount owed by one player. Internal to Subscriptions.
 *
 * @property int $id
 * @property int $organisation_id
 * @property int $player_id
 * @property string $description
 * @property int $amount_pence
 * @property CarbonImmutable $due_on
 * @property CarbonImmutable|null $paid_at
 * @property string|null $payment_reference
 * @property CarbonImmutable|null $overdue_notified_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Player $player
 */
#[Table('subscriptions_fees')]
#[Fillable(['player_id', 'description', 'amount_pence', 'due_on'])]
#[UseFactory(FeeFactory::class)]
#[ObservedBy(AuditObserver::class)]
class Fee extends Model implements Auditable
{
    use BelongsToOrganisation;

    /** @use HasFactory<FeeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_pence' => 'integer',
            'due_on' => 'immutable_date',
            'paid_at' => 'immutable_datetime',
            'overdue_notified_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->due_on->isBefore(today());
    }

    /**
     * @param  Builder<Fee>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNull('paid_at')->whereDate('due_on', '<', today());
    }

    public function toOverduePayment(): OverduePayment
    {
        return new OverduePayment(
            feeId: $this->id,
            organisationId: $this->organisation_id,
            playerId: $this->player_id,
            playerName: $this->player->full_name,
            teamId: $this->player->team_id,
            description: $this->description,
            amountPence: $this->amount_pence,
            dueOn: $this->due_on,
        );
    }

    public function auditType(): string
    {
        return 'subscriptions.fee';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
