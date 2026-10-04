<?php

declare(strict_types=1);

namespace Modules\Messaging\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Models\Team;
use App\Models\User;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A post on the club noticeboard.
 *
 * - team_id null: club-wide; otherwise for one team
 * - audience 'team': the team's coaches and admins; 'admins': admins only
 * - source: 'manual', or the module whose event created it
 *
 * @property int $id
 * @property int $organisation_id
 * @property int|null $team_id
 * @property string $audience
 * @property string $source
 * @property string $title
 * @property string|null $body
 * @property int|null $author_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Team|null $team
 * @property-read User|null $author
 */
#[Table('messaging_announcements')]
#[Fillable(['team_id', 'audience', 'source', 'title', 'body', 'author_id'])]
#[ObservedBy(AuditObserver::class)]
class Announcement extends Model implements Auditable
{
    use BelongsToOrganisation;

    public const string AUDIENCE_TEAM = 'team';

    public const string AUDIENCE_ADMINS = 'admins';

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function auditType(): string
    {
        return 'messaging.announcement';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Delete];
    }
}
