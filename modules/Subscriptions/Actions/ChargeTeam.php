<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Actions;

use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Modules\Subscriptions\Models\Fee;

/**
 * Raises the same fee for every player in a team, e.g. season subs.
 */
final class ChargeTeam
{
    /**
     * @return int number of fees created
     */
    public function handle(Team $team, string $description, int $amountPence, string $dueOn): int
    {
        return DB::transaction(function () use ($team, $description, $amountPence, $dueOn): int {
            $count = 0;

            foreach ($team->players as $player) {
                Fee::create([
                    'player_id' => $player->id,
                    'description' => $description,
                    'amount_pence' => $amountPence,
                    'due_on' => $dueOn,
                ]);
                $count++;
            }

            return $count;
        });
    }
}
