<?php

declare(strict_types=1);

use App\Models\Team;
use Modules\Fixtures\Models\Fixture;
use Modules\MatchReports\Models\MatchReport;
use Modules\MatchReports\Models\Performance;

beforeEach(fn () => $this->seed());

test('every played fixture is reported except each team\'s latest, and scorers add up to the score', function () {
    Team::withoutOrganisationScope()->whereHas('organisation', fn ($query) => $query->whereIn('slug', ['riverside-juniors-fc', 'dockside-rovers']))->get()
        ->each(function (Team $team) {
            $played = Fixture::withoutOrganisationScope()->where('team_id', $team->id)->where('kick_off_at', '<', now())->orderBy('kick_off_at')->pluck('id');
            $reported = MatchReport::withoutOrganisationScope()->whereIn('fixture_id', $played)->pluck('fixture_id');

            expect($reported->sort()->values()->all())->toBe($played->slice(0, -1)->values()->all());
        });

    MatchReport::withoutOrganisationScope()->get()->each(function (MatchReport $report) {
        $performances = Performance::withoutOrganisationScope()->where('report_id', $report->id)->get();

        expect($performances->sum('goals'))->toBe($report->goals_for)
            ->and($performances)->toHaveCount(12);
    });
});
