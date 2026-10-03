<?php

declare(strict_types=1);

namespace Modules\Availability\Queries;

use App\Models\Player;
use Modules\Availability\Models\AvailabilityResponse;
use Modules\Fixtures\Contracts\FixtureData;

/**
 * Every player in the fixture's team with their answer, if any. Players with
 * no answer yet are listed too, so a coach can see who still needs chasing.
 */
final class FixtureSquad
{
    /**
     * @return list<array{id: int, name: string, shirt_number: int|null, status: string|null, note: string|null}>
     */
    public function for(FixtureData $fixture): array
    {
        $responses = AvailabilityResponse::query()
            ->where('fixture_id', $fixture->id)
            ->get()
            ->keyBy('player_id');

        return array_values(Player::query()
            ->where('team_id', $fixture->teamId)
            ->orderBy('last_name')
            ->get()
            ->map(function (Player $player) use ($responses) {
                /** @var AvailabilityResponse|null $response */
                $response = $responses->get($player->id);

                return [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'shirt_number' => $player->shirt_number,
                    'status' => $response?->status->value,
                    'note' => $response?->note,
                ];
            })
            ->all());
    }
}
