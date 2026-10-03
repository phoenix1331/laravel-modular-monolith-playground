<?php

declare(strict_types=1);

namespace Modules\MatchReports\Models;

use App\Models\Player;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One player's goals and rating in a report. Not audited on its own: the
 * report is the unit coaches file and correct.
 *
 * @property int $id
 * @property int $organisation_id
 * @property int $report_id
 * @property int $player_id
 * @property int $goals
 * @property int|null $rating
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('match_reports_performances')]
#[Fillable(['player_id', 'goals', 'rating'])]
class Performance extends Model
{
    use BelongsToOrganisation;

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
