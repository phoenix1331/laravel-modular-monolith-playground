<?php

declare(strict_types=1);

namespace Modules\MatchReports\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The result of one played fixture. Internal to MatchReports; the fixture
 * itself is read through FixtureLookup, so there is no `fixture()` relation.
 *
 * @property int $id
 * @property int $organisation_id
 * @property int $fixture_id
 * @property int $goals_for
 * @property int $goals_against
 * @property string|null $summary
 * @property int|null $recorded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('match_reports_reports')]
#[Fillable(['fixture_id', 'goals_for', 'goals_against', 'summary', 'recorded_by'])]
#[ObservedBy(AuditObserver::class)]
class MatchReport extends Model implements Auditable
{
    use BelongsToOrganisation;

    /**
     * @return HasMany<Performance, $this>
     */
    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'report_id');
    }

    public function result(): string
    {
        return match ($this->goals_for <=> $this->goals_against) {
            1 => 'won',
            0 => 'drew',
            -1 => 'lost',
        };
    }

    public function auditType(): string
    {
        return 'match-reports.report';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
