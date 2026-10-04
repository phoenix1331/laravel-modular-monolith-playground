<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\Team;
use Modules\Fixtures\Models\Fixture;

beforeEach(fn () => $this->seed());

test('every team at a club with fixtures has three played and three upcoming', function () {
    $teams = Team::withoutOrganisationScope()->whereIn('organisation_id', Organisation::whereIn('slug', ['riverside-juniors-fc', 'dockside-rovers', 'hillcrest-ladies'])->pluck('id'))->get();

    expect($teams)->toHaveCount(8);

    $teams->each(function (Team $team) {
        $fixtures = Fixture::withoutOrganisationScope()->where('team_id', $team->id)->get();

        expect($fixtures->filter(fn (Fixture $fixture) => $fixture->kick_off_at->isPast()))->toHaveCount(3)
            ->and($fixtures->filter(fn (Fixture $fixture) => $fixture->kick_off_at->isFuture()))->toHaveCount(3)
            ->and($fixtures->pluck('organisation_id')->unique()->all())->toBe([$team->organisation_id]);
    });
});

test('a club without fixtures gets none', function () {
    $valley = Organisation::where('slug', 'valley-vets')->sole();

    expect(Fixture::withoutOrganisationScope()->where('organisation_id', $valley->id)->count())->toBe(0);
});
