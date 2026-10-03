<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Modules\Exceptions\ModuleDependencyNotEnabled;
use App\Modules\ModuleManager;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Fixtures\Contracts\FixtureLookup;
use Modules\MatchReports\Models\MatchReport;
use Modules\MatchReports\Models\Performance;

/*
| Fixtures are created through Fixtures' HTTP route and read through its
| public lookup, never its internals. Helper names are prefixed so they never
| clash with another module's test helpers when the whole suite loads.
*/

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    app(ModuleManager::class)->enable($this->club, 'fixtures');
    app(ModuleManager::class)->enable($this->club, 'match-reports');

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->team = Team::factory()->for($this->club)->create(['name' => 'U12 Lions']);
    $this->team->workers()->attach($this->coach);

    $this->sam = Player::factory()->for($this->team)->create(['first_name' => 'Sam', 'last_name' => 'Jones']);
    $this->alex = Player::factory()->for($this->team)->create(['first_name' => 'Alex', 'last_name' => 'Smith']);

    $this->played = matchReportsFixture($this->admin, $this->team, 'Played FC', '-3 days');
    $this->upcoming = matchReportsFixture($this->admin, $this->team, 'Upcoming FC', '+3 days');
});

function matchReportsFixture(User $as, Team $team, string $opponent, string $when): int
{
    test()->actingAs($as)->post(route('fixtures.store'), [
        'team_id' => $team->id,
        'opponent' => $opponent,
        'kick_off_at' => now()->modify($when)->format('Y-m-d H:i'),
        'is_home' => true,
    ])->assertSessionHasNoErrors();

    return collect(app(FixtureLookup::class)->forTeam($team->id))->firstWhere('opponent', $opponent)->id;
}

function matchReportsPayload(array $overrides = []): array
{
    return [
        'goals_for' => 3,
        'goals_against' => 1,
        'summary' => 'Strong second half.',
        'performances' => [
            ['player_id' => test()->sam->id, 'goals' => 2, 'rating' => 8],
            ['player_id' => test()->alex->id, 'goals' => 1, 'rating' => 7],
        ],
        ...$overrides,
    ];
}

test('match reports cannot be enabled before fixtures', function () {
    app(ModuleManager::class)->enable(Organisation::factory()->create(), 'match-reports');
})->throws(ModuleDependencyNotEnabled::class);

test('only played fixtures are listed, with their result once filed', function () {
    $this->actingAs($this->coach)->put(route('match-reports.update', $this->played), matchReportsPayload());

    $this->get(route('match-reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('match-reports/pages/Index')
            ->has('results', 1)
            ->where('results.0.opponent', 'Played FC')
            ->where('results.0.report', ['goals_for' => 3, 'goals_against' => 1, 'result' => 'won']));
});

test('a coach files a report with scorers and ratings', function () {
    $this->actingAs($this->coach)
        ->put(route('match-reports.update', $this->played), matchReportsPayload())
        ->assertRedirect(route('match-reports.show', $this->played));

    $report = MatchReport::sole();

    expect($report->goals_for)->toBe(3)
        ->and($report->recorded_by)->toBe($this->coach->id)
        ->and($report->performances()->orderBy('goals', 'desc')->pluck('goals', 'player_id')->all())
        ->toBe([$this->sam->id => 2, $this->alex->id => 1]);
});

test('refiling corrects the report and drops players left out', function () {
    $this->actingAs($this->coach)->put(route('match-reports.update', $this->played), matchReportsPayload());
    $this->put(route('match-reports.update', $this->played), matchReportsPayload([
        'goals_for' => 1,
        'goals_against' => 1,
        'performances' => [['player_id' => $this->sam->id, 'goals' => 1, 'rating' => 6]],
    ]));

    expect(MatchReport::sole()->result())->toBe('drew')
        ->and(Performance::pluck('player_id')->all())->toBe([$this->sam->id]);
});

test('the report page shows the filed report and the squad', function () {
    $this->actingAs($this->coach)->put(route('match-reports.update', $this->played), matchReportsPayload());

    $this->get(route('match-reports.show', $this->played))
        ->assertInertia(fn (Assert $page) => $page
            ->component('match-reports/pages/Report')
            ->where('report.result', 'won')
            ->has('players', 2)
            ->where('players.0.name', 'Sam Jones')
            ->where('players.0.goals', 2)
            ->where('can.file', true));
});

test('a report cannot be filed before kick-off', function () {
    $this->actingAs($this->admin)
        ->put(route('match-reports.update', $this->upcoming), matchReportsPayload())
        ->assertForbidden();
});

test('scorers cannot outnumber the team\'s goals', function () {
    $this->actingAs($this->coach)
        ->put(route('match-reports.update', $this->played), matchReportsPayload(['goals_for' => 2]))
        ->assertSessionHasErrors('performances');
});

test('ratings are 1 to 10', function () {
    $this->actingAs($this->coach)
        ->put(route('match-reports.update', $this->played), matchReportsPayload([
            'performances' => [['player_id' => $this->sam->id, 'goals' => 0, 'rating' => 11]],
        ]))
        ->assertSessionHasErrors('performances.0.rating');
});

test('only players from the fixture\'s team can be credited', function () {
    $outsider = Player::factory()->for(Team::factory()->for($this->club))->create();

    $this->actingAs($this->coach)
        ->put(route('match-reports.update', $this->played), matchReportsPayload([
            'goals_for' => 1,
            'performances' => [['player_id' => $outsider->id, 'goals' => 1]],
        ]))
        ->assertSessionHasErrors('performances');
});

test('a coach of another team can neither view nor file', function () {
    $otherCoach = User::factory()->worker()->forOrganisation($this->club)->create();
    Team::factory()->for($this->club)->create()->workers()->attach($otherCoach);

    $this->actingAs($otherCoach);
    $this->get(route('match-reports.show', $this->played))->assertForbidden();
    $this->put(route('match-reports.update', $this->played), matchReportsPayload())->assertForbidden();
});

test('filing is audited under the module\'s type', function () {
    $this->actingAs($this->coach)->put(route('match-reports.update', $this->played), matchReportsPayload());

    expect(AuditLog::where('subject_type', 'match-reports.report')->value('organisation_id'))->toBe($this->club->id);
});

test('a club with fixtures but not match reports gets a 404', function () {
    auth()->logout();
    $club = Organisation::factory()->create();
    app(ModuleManager::class)->enable($club, 'fixtures');

    $this->actingAs(User::factory()->admin()->forOrganisation($club)->create())
        ->get(route('match-reports.index'))
        ->assertNotFound();
});
