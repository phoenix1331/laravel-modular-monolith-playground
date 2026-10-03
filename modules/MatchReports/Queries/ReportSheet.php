<?php

declare(strict_types=1);

namespace Modules\MatchReports\Queries;

use App\Models\Player;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\MatchReports\Models\MatchReport;
use Modules\MatchReports\Models\Performance;

/**
 * The report form for one fixture: the filed report (if any) and every
 * player in the team with their goals and rating.
 */
final class ReportSheet
{
    /**
     * Whether every given player is in the fixture's team.
     *
     * @param  list<int>  $playerIds
     */
    public function allInTeam(FixtureData $fixture, array $playerIds): bool
    {
        return Player::query()->where('team_id', $fixture->teamId)->whereKey($playerIds)->count() === count($playerIds);
    }

    /**
     * @return array{report: MatchReport|null, players: list<array{id: int, name: string, shirt_number: int|null, goals: int, rating: int|null}>}
     */
    public function for(FixtureData $fixture): array
    {
        $report = MatchReport::query()->with('performances')->where('fixture_id', $fixture->id)->first();
        $performances = $report?->performances->keyBy('player_id') ?? collect();

        $players = Player::query()
            ->where('team_id', $fixture->teamId)
            ->orderBy('last_name')
            ->get()
            ->map(function (Player $player) use ($performances) {
                /** @var Performance|null $performance */
                $performance = $performances->get($player->id);

                return [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'shirt_number' => $player->shirt_number,
                    'goals' => $performance->goals ?? 0,
                    'rating' => $performance?->rating,
                ];
            });

        return ['report' => $report, 'players' => array_values($players->all())];
    }
}
