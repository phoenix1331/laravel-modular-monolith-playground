<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\PlayerRequest;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Players are listed on their team's page, so this controller only handles
 * adding, editing and removing them. Routes are shallow: creating needs the
 * team, everything after that only needs the player.
 */
class PlayerController extends Controller
{
    public function create(Team $team): Response
    {
        Gate::authorize('create', [Player::class, $team]);

        return Inertia::render('players/Create', [
            'team' => ['id' => $team->id, 'name' => $team->name],
        ]);
    }

    public function store(PlayerRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('create', [Player::class, $team]);

        $team->players()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player added.')]);

        return to_route('teams.show', $team);
    }

    public function edit(Player $player): Response
    {
        Gate::authorize('update', $player);

        return Inertia::render('players/Edit', [
            'team' => ['id' => $player->team->id, 'name' => $player->team->name],
            'player' => [
                'id' => $player->id,
                'first_name' => $player->first_name,
                'last_name' => $player->last_name,
                'date_of_birth' => $player->date_of_birth?->toDateString(),
                'shirt_number' => $player->shirt_number,
            ],
        ]);
    }

    public function update(PlayerRequest $request, Player $player): RedirectResponse
    {
        Gate::authorize('update', $player);

        $player->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player updated.')]);

        return to_route('teams.show', $player->team_id);
    }

    public function destroy(Player $player): RedirectResponse
    {
        Gate::authorize('delete', $player);

        $player->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player removed.')]);

        return to_route('teams.show', $player->team_id);
    }
}
