<?php

declare(strict_types=1);

namespace Modules\Compliance\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Models\User;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One certificate held by one worker. Internal to Compliance.
 *
 * @property int $id
 * @property int $organisation_id
 * @property int $user_id
 * @property string $type
 * @property string|null $reference
 * @property CarbonImmutable $issued_on
 * @property CarbonImmutable $expires_on
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Table('compliance_certificates')]
#[Fillable(['user_id', 'type', 'reference', 'issued_on', 'expires_on'])]
#[ObservedBy(AuditObserver::class)]
class Certificate extends Model implements Auditable
{
    use BelongsToOrganisation;

    public const array TYPES = ['DBS check', 'Safeguarding Children', 'First Aid'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['issued_on' => 'immutable_date', 'expires_on' => 'immutable_date'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): CertificateStatus
    {
        return CertificateStatus::for($this->expires_on);
    }

    public function auditType(): string
    {
        return 'compliance.certificate';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
