<?php

declare(strict_types=1);

namespace Modules\MatchReports\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\MatchReports\Models\MatchReport;

/**
 * Files or corrects the report for a fixture: the score plus one row per
 * player listed. Players left out of the list lose their row, so the form
 * always describes the whole report.
 */
final class FileMatchReport
{
    /**
     * @param  array{goals_for: int, goals_against: int, summary?: string|null, performances?: list<array{player_id: int, goals?: int, rating?: int|null}>}  $details
     */
    public function handle(FixtureData $fixture, array $details, User $by): MatchReport
    {
        return DB::transaction(function () use ($fixture, $details, $by) {
            $report = MatchReport::updateOrCreate(
                ['fixture_id' => $fixture->id],
                [
                    'goals_for' => $details['goals_for'],
                    'goals_against' => $details['goals_against'],
                    'summary' => $details['summary'] ?? null,
                    'recorded_by' => $by->id,
                ],
            );

            $rows = collect($details['performances'] ?? []);

            $report->performances()->whereNotIn('player_id', $rows->pluck('player_id'))->delete();

            foreach ($rows as $row) {
                $report->performances()->updateOrCreate(
                    ['player_id' => $row['player_id']],
                    ['goals' => $row['goals'] ?? 0, 'rating' => $row['rating'] ?? null],
                );
            }

            return $report;
        });
    }
}
