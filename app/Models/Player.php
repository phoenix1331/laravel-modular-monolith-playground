<?php

declare(strict_types=1);

namespace App\Models;

use App\Tenancy\BelongsToOrganisation;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A player on a team. A record managed by club admins, never a login.
 *
 * @property int $id
 * @property int $organisation_id
 * @property int $team_id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $date_of_birth
 * @property int|null $shirt_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 */
#[Fillable(['first_name', 'last_name', 'date_of_birth', 'shirt_number'])]
class Player extends Model
{
    use BelongsToOrganisation;

    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'shirt_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
