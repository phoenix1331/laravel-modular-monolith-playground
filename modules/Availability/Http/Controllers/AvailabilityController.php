<?php

declare(strict_types=1);

namespace Modules\Availability\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Availability\Http\Requests\MarkAvailabilityRequest;
use Modules\Availability\Models\AvailabilityResponse;
use Modules\Availability\Queries\FixtureSquad;
use Modules\Availability\Queries\UpcomingAvailability;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Contracts\FixtureLookup;

/**
 * Routes take a plain fixture id, not a bound Fixture model: the model
 * belongs to another module. The id is resolved through FixtureLookup, which
 * answers for the current club only, so another club's fixture is a 404.
 */
class AvailabilityController extends Controller
{
    public function __construct(private readonly FixtureLookup $fixtures) {}

    public function index(Request $request, UpcomingAvailability $upcoming): Response
    {
        Gate::authorize('viewAny', AvailabilityResponse::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('availability/pages/Index', [
            'fixtures' => array_map(fn (array $row) => [
                ...$this->fixtureProps($row['fixture']),
                'squad' => $row['squad'],
                'counts' => $row['counts'],
            ], $upcoming->for($user)),
        ]);
    }

    public function show(Request $request, int $fixture, FixtureSquad $squad): Response
    {
        $data = $this->resolve($fixture);
        Gate::authorize('view', [AvailabilityResponse::class, $data]);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('availability/pages/Fixture', [
            'fixture' => $this->fixtureProps($data),
            'players' => $squad->for($data),
            'can' => ['mark' => $user->can('mark', [AvailabilityResponse::class, $data])],
        ]);
    }

    public function update(MarkAvailabilityRequest $request, int $fixture, Player $player): RedirectResponse
    {
        $data = $this->resolve($fixture);
        Gate::authorize('mark', [AvailabilityResponse::class, $data]);

        // The player must be in the fixture's team, not just in the same club
        abort_unless($player->team_id === $data->teamId, 404);

        AvailabilityResponse::updateOrCreate(
            ['fixture_id' => $data->id, 'player_id' => $player->id],
            [...$request->validated(), 'recorded_by' => $request->user()?->id],
        );

        return back();
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
            'venue' => $fixture->venue,
        ];
    }
}
