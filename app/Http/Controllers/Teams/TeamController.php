<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\TeamRequest;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Queries\ClubWorkers;
use App\Queries\VisibleTeams;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Teams for the signed-in user's club. The {team} route parameter resolves
 * through OrganisationScope, so another club's team id is a 404 here.
 */
class TeamController extends Controller
{
    public function index(Request $request, VisibleTeams $teams): Response
    {
        Gate::authorize('viewAny', Team::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('teams/Index', [
            'teams' => $teams->for($user)->get()->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'age_group' => $team->age_group,
                'players_count' => $team->players_count,
            ]),
            'can' => ['create' => $user->can('create', Team::class)],
        ]);
    }

    public function create(ClubWorkers $workers): Response
    {
        Gate::authorize('create', Team::class);

        return Inertia::render('teams/Create', [
            'workers' => $workers->query()->get(['id', 'name']),
        ]);
    }

    public function store(TeamRequest $request): RedirectResponse
    {
        Gate::authorize('create', Team::class);

        $team = Team::create($request->safe()->only(['name', 'age_group']));
        $team->workers()->sync($request->validated('worker_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return to_route('teams.show', $team);
    }

    public function show(Request $request, Team $team): Response
    {
        Gate::authorize('view', $team);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'age_group' => $team->age_group,
            ],
            'players' => $team->players()->orderBy('last_name')->get()->map(fn (Player $player) => [
                'id' => $player->id,
                'full_name' => $player->full_name,
                'shirt_number' => $player->shirt_number,
                'date_of_birth' => $player->date_of_birth?->toDateString(),
            ]),
            'workers' => $team->workers()->orderBy('name')->get(['users.id', 'users.name']),
            'can' => [
                'update' => $user->can('update', $team),
                'delete' => $user->can('delete', $team),
                'managePlayers' => $user->can('create', [Player::class, $team]),
            ],
        ]);
    }

    public function edit(Team $team, ClubWorkers $workers): Response
    {
        Gate::authorize('update', $team);

        return Inertia::render('teams/Edit', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'age_group' => $team->age_group,
                'worker_ids' => $team->workers()->pluck('users.id'),
            ],
            'workers' => $workers->query()->get(['id', 'name']),
        ]);
    }

    public function update(TeamRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $team->update($request->safe()->only(['name', 'age_group']));
        $team->workers()->sync($request->validated('worker_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('teams.show', $team);
    }

    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        $team->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('teams.index');
    }
}
