<?php

declare(strict_types=1);

use App\Models\Organisation;
use App\Models\User;
use App\Tenancy\CrossOrganisationWrite;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Note;

beforeEach(function () {
    Schema::create('notes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('organisation_id')->constrained();
        $table->string('body');
        $table->timestamps();
    });

    $this->riverside = Organisation::factory()->create(['name' => 'Riverside Juniors FC']);
    $this->dockside = Organisation::factory()->create(['name' => 'Dockside Rovers']);

    app(CurrentOrganisation::class)->runAs($this->riverside, fn () => Note::create(['body' => 'riverside note']));
    app(CurrentOrganisation::class)->runAs($this->dockside, fn () => Note::create(['body' => 'dockside note']));

    $this->riversideWorker = User::factory()->worker()->forOrganisation($this->riverside)->create();
});

test('a club user only reads their own organisation', function () {
    $this->actingAs($this->riversideWorker);

    expect(Note::pluck('body')->all())->toBe(['riverside note']);
});

test('another organisation\'s record cannot be found by id', function () {
    $this->actingAs($this->riversideWorker);
    $docksideNote = Note::withoutOrganisationScope()->where('body', 'dockside note')->sole();

    expect(Note::find($docksideNote->id))->toBeNull();
    Note::findOrFail($docksideNote->id);
})->throws(ModelNotFoundException::class);

test('creating fills organisation_id from the signed-in user', function () {
    $this->actingAs($this->riversideWorker);

    $note = Note::create(['body' => 'new']);

    expect($note->organisation_id)->toBe($this->riverside->id);
});

test('creating a record for another organisation throws', function () {
    $this->actingAs($this->riversideWorker);

    Note::create(['body' => 'sneaky', 'organisation_id' => $this->dockside->id]);
})->throws(CrossOrganisationWrite::class);

test('a record cannot be moved to another organisation', function () {
    $this->actingAs($this->riversideWorker);
    $note = Note::sole();

    $note->update(['organisation_id' => $this->dockside->id]);
})->throws(CrossOrganisationWrite::class);

test('bulk updates and deletes never reach another organisation', function () {
    $this->actingAs($this->riversideWorker);

    Note::query()->update(['body' => 'overwritten']);
    Note::query()->delete();

    expect(Note::withoutOrganisationScope()->pluck('body')->all())->toBe(['dockside note']);
});

test('with no current organisation the scope matches nothing', function (?string $who) {
    if ($who === 'super admin') {
        $this->actingAs(User::factory()->superAdmin()->create());
    }

    expect(Note::count())->toBe(0);
})->with(['guest' => null, 'super admin' => 'super admin']);

test('creating with no current organisation and no explicit id throws', function () {
    Note::create(['body' => 'orphan']);
})->throws(CrossOrganisationWrite::class);

test('the super admin reads across organisations only by bypassing the scope', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    expect(Note::withoutOrganisationScope()->orderBy('body')->pluck('body')->all())
        ->toBe(['dockside note', 'riverside note']);
});

test('code without a user can run as an organisation explicitly', function () {
    $bodies = app(CurrentOrganisation::class)->runAs($this->dockside, fn () => Note::pluck('body')->all());

    expect($bodies)->toBe(['dockside note'])
        ->and(app(CurrentOrganisation::class)->check())->toBeFalse();
});
