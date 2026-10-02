<?php

declare(strict_types=1);

use App\Audit\AuditAction;
use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fixtures\CreateOnlyNote;

beforeEach(function () {
    $this->club = Organisation::factory()->create(['name' => 'Riverside Juniors FC']);
    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create(['name' => 'Club Admin']);

    // Setup above is audited too; start each test with an empty trail
    AuditLog::query()->toBase()->delete();
});

function lastEntry(): AuditLog
{
    return AuditLog::query()->latest('id')->firstOrFail();
}

test('creating a record logs who, where and a snapshot', function () {
    $this->actingAs($this->admin)->post(route('teams.store'), ['name' => 'U12 Lions', 'age_group' => 'U12']);

    $entry = lastEntry();
    $team = Team::sole();

    expect($entry->action)->toBe(AuditAction::Create)
        ->and($entry->subject_type)->toBe('team')
        ->and($entry->subject_id)->toBe((string) $team->id)
        ->and($entry->organisation_id)->toBe($this->club->id)
        ->and($entry->user_id)->toBe($this->admin->id)
        ->and($entry->request_route)->toBe('teams.store')
        ->and($entry->event_data['attributes'])->toMatchArray(['name' => 'U12 Lions', 'age_group' => 'U12'])
        ->and($entry->event_data['attributes'])->not->toHaveKeys(['created_at', 'updated_at']);
});

test('updating a record logs only the fields that changed', function () {
    $team = Team::factory()->for($this->club)->create(['name' => 'U12 Lions', 'age_group' => 'U12']);

    $this->actingAs($this->admin)->put(route('teams.update', $team), ['name' => 'U12 Lionesses', 'age_group' => 'U12']);

    expect(lastEntry()->action)->toBe(AuditAction::Update)
        ->and(lastEntry()->event_data['changes'])->toBe([
            'name' => ['from' => 'U12 Lions', 'to' => 'U12 Lionesses'],
        ]);
});

test('an update that only touches timestamps is not logged', function () {
    $team = Team::factory()->for($this->club)->create();
    $before = AuditLog::count();

    $this->travel(1)->minutes();
    $team->touch();

    expect(AuditLog::count())->toBe($before);
});

test('deleting a record keeps a snapshot of what it was', function () {
    $team = Team::factory()->for($this->club)->create(['name' => 'U14 Hawks']);

    $this->actingAs($this->admin)->delete(route('teams.destroy', $team));

    expect(lastEntry()->action)->toBe(AuditAction::Delete)
        ->and(lastEntry()->event_data['attributes']['name'])->toBe('U14 Hawks');
});

test('hidden attributes such as passwords never reach the log', function () {
    $this->actingAs($this->admin)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'a-new-password',
        'password_confirmation' => 'a-new-password',
    ])->assertSessionHasNoErrors();

    $raw = AuditLog::query()->pluck('event_data')->implode(' ');

    expect($raw)->not->toContain('password')
        ->and(AuditLog::where('action', AuditAction::Update)->count())->toBe(0);
});

test('a change to club data lands in that club\'s trail even with no current organisation', function () {
    $team = Team::factory()->for($this->club)->create();

    $this->actingAs(User::factory()->superAdmin()->create());
    Team::withoutOrganisationScope()->findOrFail($team->id)->update(['name' => 'Renamed by platform']);

    expect(lastEntry()->organisation_id)->toBe($this->club->id);
});

test('a model can opt out of auditing some actions', function () {
    Schema::create('notes', function (Blueprint $table) {
        $table->id();
        $table->string('body');
        $table->timestamps();
    });

    $note = CreateOnlyNote::create(['body' => 'first']);
    $note->update(['body' => 'second']);
    $note->delete();

    expect(AuditLog::where('subject_type', 'note')->pluck('action')->all())->toBe([AuditAction::Create]);
});

test('logins and logouts are recorded', function () {
    $this->post(route('login.store'), ['email' => $this->admin->email, 'password' => 'password']);
    expect(lastEntry()->action)->toBe(AuditAction::Login)
        ->and(lastEntry()->organisation_id)->toBe($this->club->id);

    $this->post(route('logout'));
    expect(lastEntry()->action)->toBe(AuditAction::Logout);
});

test('audit entries cannot be changed or deleted', function (string $operation) {
    $this->actingAs($this->admin)->post(route('teams.store'), ['name' => 'U12 Lions']);
    $entry = lastEntry();

    $operation === 'update' ? $entry->update(['subject_type' => 'tampered']) : $entry->delete();
})->with(['update', 'delete'])->throws(LogicException::class);

test('a club admin reads only their own club\'s trail', function () {
    $otherClub = Organisation::factory()->create();
    $otherAdmin = User::factory()->admin()->forOrganisation($otherClub)->create();

    $this->actingAs($this->admin)->post(route('teams.store'), ['name' => 'Ours']);
    $this->actingAs($otherAdmin)->post(route('teams.store'), ['name' => 'Theirs']);

    $this->actingAs($this->admin)
        ->get(route('audit.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('audit/Index')
            ->where('showOrganisation', false)
            ->has('entries.data', 1)
            ->where('entries.data.0.subject_label', 'Ours')
            ->where('entries.data.0.user', 'Club Admin'));
});

test('the super admin reads every club\'s trail with organisation names', function () {
    $this->actingAs($this->admin)->post(route('teams.store'), ['name' => 'Ours']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('platform.audit.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('showOrganisation', true)
            ->where('entries.data.0.organisation', 'Riverside Juniors FC'));
});

test('workers cannot read the audit log', function () {
    $this->actingAs(User::factory()->worker()->forOrganisation($this->club)->create())
        ->get(route('audit.index'))
        ->assertForbidden();
});
