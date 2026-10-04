<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\Team;
use Modules\Availability\Models\AvailabilityResponse;
use Modules\Fixtures\Models\Fixture;

beforeEach(fn () => $this->seed());

test('replies taper off: most of the squad for the next fixture, half for the one after, none for the third', function () {
    $team = Team::withoutOrganisationScope()->where('name', 'U11 Badgers')->sole();
    $upcoming = Fixture::withoutOrganisationScope()->where('team_id', $team->id)->where('kick_off_at', '>', now())->orderBy('kick_off_at')->pluck('id');

    $replies = $upcoming->map(fn (int $id) => AvailabilityResponse::withoutOrganisationScope()->where('fixture_id', $id)->count())->all();

    expect($replies)->toBe([10, 6, 0]);
});

test('Hillcrest has fixtures but no availability data', function () {
    $hillcrest = Organisation::where('slug', 'hillcrest-ladies')->sole();

    expect(AvailabilityResponse::withoutOrganisationScope()->where('organisation_id', $hillcrest->id)->count())->toBe(0);
});
