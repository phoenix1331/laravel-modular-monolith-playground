<?php

declare(strict_types=1);

namespace Modules\Fixtures\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Fixtures\Actions\ScheduleFixture;
use Modules\Fixtures\Http\Requests\FixtureRequest;
use Modules\Fixtures\Models\Fixture;
use Modules\Fixtures\Models\Venue;
use Modules\Fixtures\Queries\FixtureFormOptions;
use Modules\Fixtures\Queries\FixturesForUser;

/**
 * Every route here sits behind `module:fixtures` (added by
 * ModuleServiceProvider), so a club without the module gets a 404 before
 * any of this runs.
 *
 * Pages are rendered by their path under resources/js/modules, e.g.
 * `fixtures/pages/Index` is resources/js/modules/fixtures/pages/Index.vue.
 */
class FixtureController extends Controller
{
    public function index(Request $request, FixturesForUser $fixtures): Response
    {
        Gate::authorize('viewAny', Fixture::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('fixtures/pages/Index', [
            'upcoming' => $this->rows($user, $fixtures->query($user)->where('kick_off_at', '>=', now())->orderBy('kick_off_at')->limit(50)->get()),
            'results' => $this->rows($user, $fixtures->query($user)->where('kick_off_at', '<', now())->orderByDesc('kick_off_at')->limit(20)->get()),
            'can' => [
                'create' => $user->can('createAny', Fixture::class),
                'manageVenues' => $user->can('viewAny', Venue::class),
            ],
        ]);
    }

    public function create(Request $request, FixtureFormOptions $options): Response
    {
        Gate::authorize('createAny', Fixture::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('fixtures/pages/Create', [
            'teams' => $options->teamsFor($user),
            'venues' => $options->venues(),
        ]);
    }

    public function store(FixtureRequest $request, ScheduleFixture $schedule): RedirectResponse
    {
        $team = Team::findOrFail($request->integer('team_id'));
        Gate::authorize('create', [Fixture::class, $team]);

        $schedule->handle($team, $request->safe()->except('team_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fixture scheduled.')]);

        return to_route('fixtures.index');
    }

    public function edit(Fixture $fixture, FixtureFormOptions $options): Response
    {
        Gate::authorize('update', $fixture);

        return Inertia::render('fixtures/pages/Edit', [
            'fixture' => [
                'id' => $fixture->id,
                'team' => $fixture->team->name,
                'venue_id' => $fixture->venue_id,
                'opponent' => $fixture->opponent,
                'kick_off_at' => $fixture->kick_off_at->format('Y-m-d\TH:i'),
                'is_home' => $fixture->is_home,
                'competition' => $fixture->competition,
            ],
            'venues' => $options->venues(),
        ]);
    }

    public function update(FixtureRequest $request, Fixture $fixture): RedirectResponse
    {
        Gate::authorize('update', $fixture);

        $fixture->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fixture updated.')]);

        return to_route('fixtures.index');
    }

    public function destroy(Fixture $fixture): RedirectResponse
    {
        Gate::authorize('delete', $fixture);

        $fixture->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fixture removed.')]);

        return to_route('fixtures.index');
    }

    /**
     * @param  iterable<Fixture>  $fixtures
     * @return list<array<string, mixed>>
     */
    private function rows(User $user, iterable $fixtures): array
    {
        $rows = [];

        foreach ($fixtures as $fixture) {
            $rows[] = [
                'id' => $fixture->id,
                'team' => $fixture->team->name,
                'opponent' => $fixture->opponent,
                'kick_off_at' => $fixture->kick_off_at->toIso8601String(),
                'is_home' => $fixture->is_home,
                'venue' => $fixture->venue?->name,
                'competition' => $fixture->competition,
                'can_update' => $user->can('update', $fixture),
            ];
        }

        return $rows;
    }
}
