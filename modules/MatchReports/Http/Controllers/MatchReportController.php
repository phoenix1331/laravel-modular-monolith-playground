<?php

declare(strict_types=1);

namespace Modules\MatchReports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Contracts\FixtureLookup;
use Modules\MatchReports\Actions\FileMatchReport;
use Modules\MatchReports\Http\Requests\FileReportRequest;
use Modules\MatchReports\Models\MatchReport;
use Modules\MatchReports\Queries\RecentResults;
use Modules\MatchReports\Queries\ReportSheet;

/**
 * Like Availability, routes take a plain fixture id resolved through the
 * public FixtureLookup: another club's fixture, or one that does not exist,
 * is a 404.
 */
class MatchReportController extends Controller
{
    public function __construct(private readonly FixtureLookup $fixtures) {}

    public function index(Request $request, RecentResults $results): Response
    {
        Gate::authorize('viewAny', MatchReport::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('match-reports/pages/Index', [
            'results' => array_map(fn (array $row) => [
                ...$this->fixtureProps($row['fixture']),
                'report' => $row['report'] === null ? null : [
                    'goals_for' => $row['report']->goals_for,
                    'goals_against' => $row['report']->goals_against,
                    'result' => $row['report']->result(),
                ],
            ], $results->for($user)),
        ]);
    }

    public function show(Request $request, int $fixture, ReportSheet $sheet): Response
    {
        $data = $this->resolve($fixture);
        Gate::authorize('view', [MatchReport::class, $data]);

        /** @var User $user */
        $user = $request->user();
        ['report' => $report, 'players' => $players] = $sheet->for($data);

        return Inertia::render('match-reports/pages/Report', [
            'fixture' => $this->fixtureProps($data),
            'report' => $report === null ? null : [
                'goals_for' => $report->goals_for,
                'goals_against' => $report->goals_against,
                'summary' => $report->summary,
                'result' => $report->result(),
            ],
            'players' => $players,
            'can' => [
                'file' => $user->can('file', [MatchReport::class, $data]),
                'notPlayedYet' => ! $data->hasKickedOff(),
            ],
        ]);
    }

    public function update(FileReportRequest $request, int $fixture, FileMatchReport $file, ReportSheet $sheet): RedirectResponse
    {
        $data = $this->resolve($fixture);
        Gate::authorize('file', [MatchReport::class, $data]);

        /** @var array{goals_for: int, goals_against: int, summary?: string|null, performances?: list<array{player_id: int, goals?: int, rating?: int|null}>} $details */
        $details = $request->validated();
        if (! $sheet->allInTeam($data, array_column($details['performances'] ?? [], 'player_id'))) {
            throw ValidationException::withMessages(['performances' => 'Every player must be in the fixture\'s team.']);
        }

        /** @var User $user */
        $user = $request->user();
        $file->handle($data, $details, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match report saved.')]);

        return to_route('match-reports.show', $data->id);
    }

    private function resolve(int $fixtureId): FixtureData
    {
        return $this->fixtures->find($fixtureId) ?? abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixtureProps(FixtureData $fixture): array
    {
        return [
            'id' => $fixture->id,
            'team' => $fixture->teamName,
            'opponent' => $fixture->opponent,
            'kick_off_at' => $fixture->kickOffAt->toIso8601String(),
            'is_home' => $fixture->isHome,
            'competition' => $fixture->competition,
        ];
    }
}
