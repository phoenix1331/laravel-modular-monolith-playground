<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\User;
use App\Modules\ModuleManager;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Compliance\Models\Certificate;
use Modules\Compliance\Models\CertificateStatus;

beforeEach(function () {
    $this->club = Organisation::factory()->create();
    app(ModuleManager::class)->enable($this->club, 'compliance');

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->expired = User::factory()->worker()->forOrganisation($this->club)->create(['name' => 'Ann Expired']);
    $this->expiring = User::factory()->worker()->forOrganisation($this->club)->create(['name' => 'Bob Expiring']);
    $this->valid = User::factory()->worker()->forOrganisation($this->club)->create(['name' => 'Cat Valid']);
    $this->missing = User::factory()->worker()->forOrganisation($this->club)->create(['name' => 'Dan Missing']);

    $this->actingAs($this->admin);
    complianceCertificate($this->expired, 'Safeguarding Children', now()->subMonth());
    complianceCertificate($this->expired, 'DBS check', now()->addYear());
    complianceCertificate($this->expiring, 'First Aid', now()->addWeeks(3));
    complianceCertificate($this->valid, 'DBS check', now()->addYear());
});

function complianceCertificate(User $worker, string $type, DateTimeInterface $expires): void
{
    test()->post(route('compliance.certificates.store'), [
        'user_id' => $worker->id,
        'type' => $type,
        'issued_on' => now()->subYears(3)->toDateString(),
        'expires_on' => $expires->format('Y-m-d'),
    ])->assertSessionHasNoErrors();
}

test('status is worked out from the expiry date', function () {
    expect(CertificateStatus::for(CarbonImmutable::yesterday()))->toBe(CertificateStatus::Expired)
        ->and(CertificateStatus::for(CarbonImmutable::today()))->toBe(CertificateStatus::Expiring)
        ->and(CertificateStatus::for(CarbonImmutable::today()->addDays(59)))->toBe(CertificateStatus::Expiring)
        ->and(CertificateStatus::for(CarbonImmutable::today()->addDays(60)))->toBe(CertificateStatus::Valid);
});

test('the register lists every coach by their worst status, including those with none', function () {
    $this->get(route('compliance.index', ['status' => 'all']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('compliance/pages/Register')
            ->where('workers.0.name', 'Dan Missing')
            ->where('workers.0.status', 'missing')
            ->where('workers.1.name', 'Ann Expired')
            ->where('workers.1.status', 'expired')
            ->where('workers.2.status', 'expiring')
            ->where('workers.3.status', 'valid'));
});

test('the default view shows only coaches needing attention', function () {
    $this->get(route('compliance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filter', 'attention')
            ->has('workers', 3));

    $this->get(route('compliance.index', ['status' => 'expired']))
        ->assertInertia(fn (Assert $page) => $page->has('workers', 1)->where('workers.0.name', 'Ann Expired'));
});

test('a worker sees only their own certificates', function () {
    $this->actingAs($this->expired)->get(route('compliance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('compliance/pages/Mine')
            ->has('certificates', 2)
            ->where('certificates.0.status', 'expired'));
});

test('workers cannot record or remove certificates', function () {
    $certificate = Certificate::where('user_id', $this->valid->id)->sole();

    $this->actingAs($this->valid);
    $this->post(route('compliance.certificates.store'), [
        'user_id' => $this->valid->id, 'type' => 'First Aid', 'issued_on' => now()->toDateString(), 'expires_on' => now()->addYears(5)->toDateString(),
    ])->assertForbidden();
    $this->delete(route('compliance.certificates.destroy', $certificate))->assertForbidden();
});

test('certificates are for workers in this club, and expire after they are issued', function () {
    auth()->logout();
    $outsider = User::factory()->worker()->create();

    $this->actingAs($this->admin)
        ->post(route('compliance.certificates.store'), [
            'user_id' => $outsider->id,
            'type' => 'Knitting',
            'issued_on' => now()->toDateString(),
            'expires_on' => now()->subDay()->toDateString(),
        ])
        ->assertSessionHasErrors(['user_id', 'type', 'expires_on']);
});

test('certificates are audited under the module\'s type', function () {
    expect(AuditLog::where('subject_type', 'compliance.certificate')->count())->toBe(4);
});

test('a club without compliance gets a 404', function () {
    auth()->logout();
    $club = Organisation::factory()->create();

    $this->actingAs(User::factory()->admin()->forOrganisation($club)->create())
        ->get(route('compliance.index'))
        ->assertNotFound();
});

test('seeding gives Riverside an expired safeguarding certificate and other clubs none', function () {
    auth()->logout();
    $this->seed();

    $gareth = User::where('email', 'coach3@riverside.test')->sole();
    $this->actingAs($gareth)->get(route('compliance.index'))
        ->assertInertia(fn (Assert $page) => $page->where('certificates', fn ($certificates) => collect($certificates)
            ->contains(fn ($row) => $row['type'] === 'Safeguarding Children' && $row['status'] === 'expired')
            && collect($certificates)->where('type', 'Safeguarding Children')->count() === 1));

    $dockside = Organisation::where('slug', 'dockside-rovers')->sole();
    expect(Certificate::withoutOrganisationScope()->where('organisation_id', $dockside->id)->count())->toBe(0);
});
