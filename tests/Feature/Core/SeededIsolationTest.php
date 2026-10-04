<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Every seeded club user, one by one: what they can list, open and read is
| only ever their own club's. Complements the targeted scoping tests with a
| sweep across realistic data.
*/

beforeEach(function () {
    $this->seed();

    // Give every club some audit history to leak, if anything leaked
    Organisation::all()->each(fn (Organisation $club) => AuditLog::create([
        'organisation_id' => $club->id,
        'action' => 'update',
        'subject_type' => 'team',
        'event_data' => ['marker' => $club->slug],
    ]));
});

test('club users only list their own club\'s teams', function () {
    User::where('role', '!=', Role::SuperAdmin)->get()->each(function (User $user) {
        $ownTeamIds = Team::withoutOrganisationScope()->where('organisation_id', $user->organisation_id)->pluck('id');

        $this->actingAs($user)
            ->get(route('teams.index'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'teams',
                fn ($teams) => collect($teams)->pluck('id')->diff($ownTeamIds)->isEmpty(),
            ));
    });
});

test('club users cannot open any other club\'s team', function () {
    User::where('role', '!=', Role::SuperAdmin)->get()->each(function (User $user) {
        $this->actingAs($user);

        Team::withoutOrganisationScope()
            ->where('organisation_id', '!=', $user->organisation_id)
            ->get()
            ->each(fn (Team $team) => $this->get(route('teams.show', $team))->assertNotFound());
    });
});

test('club admins only read their own club\'s audit trail', function () {
    User::where('role', Role::Admin)->get()->each(function (User $admin) {
        // Seeding also records module toggles, so compare with the club's own entries rather than a fixed count
        $ownEntries = AuditLog::where('organisation_id', $admin->organisation_id)->count();

        $this->actingAs($admin)
            ->get(route('audit.index'))
            ->assertInertia(fn (Assert $page) => $page->has('entries.data', min($ownEntries, 25)));

        expect(AuditLog::where('organisation_id', $admin->organisation_id)->whereNotNull('event_data->marker')->sole()->event_data['marker'])
            ->toBe($admin->organisation->slug);
    });
});
