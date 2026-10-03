<?php

declare(strict_types=1);

namespace Modules\Fixtures\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Models\Team;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Database\Factories\FixtureFactory;

/**
 * A match for one of the club's teams. Internal to Fixtures: other modules
 * see FixtureData through FixtureLookup, never this model.
 *
 * The relation to Core's Team points one way only. Fixtures knows about
 * teams; Team has no `fixtures()` relation, because Core never references a
 * module.
 *
 * @property int $id
 * @property int $organisation_id
 * @property int $team_id
 * @property int|null $venue_id
 * @property string $opponent
 * @property CarbonImmutable $kick_off_at
 * @property bool $is_home
 * @property string|null $competition
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Team $team
 * @property-read Venue|null $venue
 */
#[Table('fixtures_fixtures')]
#[Fillable(['team_id', 'venue_id', 'opponent', 'kick_off_at', 'is_home', 'competition'])]
#[UseFactory(FixtureFactory::class)]
#[ObservedBy(AuditObserver::class)]
class Fixture extends Model implements Auditable
{
    use BelongsToOrganisation;

    /** @use HasFactory<FixtureFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kick_off_at' => 'immutable_datetime',
            'is_home' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * The public shape of this fixture, for the lookup and events.
     */
    public function toData(): FixtureData
    {
        return new FixtureData(
            id: $this->id,
            organisationId: $this->organisation_id,
            teamId: $this->team_id,
            teamName: $this->team->name,
            opponent: $this->opponent,
            kickOffAt: $this->kick_off_at,
            isHome: $this->is_home,
            venue: $this->venue?->name,
            competition: $this->competition,
        );
    }

    public function auditType(): string
    {
        return 'fixtures.fixture';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update, AuditAction::Delete];
    }
}
