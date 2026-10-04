<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Subscriptions\Contracts\Events\PaymentOverdue;
use Modules\Subscriptions\Contracts\OverduePayment;
use Modules\Subscriptions\Models\Fee;
use Modules\Subscriptions\Payments\PaymentGateway;

beforeEach(function () {
    $this->club = Organisation::factory()->create(['name' => 'Riverside Juniors FC']);
    app(ModuleManager::class)->enable($this->club, 'subscriptions');

    $this->admin = User::factory()->admin()->forOrganisation($this->club)->create();
    $this->coach = User::factory()->worker()->forOrganisation($this->club)->create();

    $this->team = Team::factory()->for($this->club)->create(['name' => 'U12 Lions']);
    $this->players = Player::factory()->count(3)->for($this->team)->create();
});

describe('raising fees', function () {
    test('charging a team raises one fee per player, stored in pence', function () {
        $this->actingAs($this->admin)
            ->post(route('subscriptions.fees.store'), [
                'team_id' => $this->team->id,
                'description' => '2026/27 season subs',
                'amount' => '120.50',
                'due_on' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('subscriptions.index'));

        expect(Fee::count())->toBe(3)
            ->and(Fee::pluck('amount_pence')->unique()->all())->toBe([12050])
            ->and(Fee::pluck('organisation_id')->unique()->all())->toBe([$this->club->id]);
    });

    test('another club\'s team cannot be charged', function () {
        auth()->logout();
        $foreignTeam = Team::factory()->for(Organisation::factory())->create();

        $this->actingAs($this->admin)
            ->post(route('subscriptions.fees.store'), [
                'team_id' => $foreignTeam->id,
                'description' => 'Sneaky',
                'amount' => 10,
                'due_on' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('team_id');
    });

    test('fees are audited under the module\'s type', function () {
        Fee::factory()->for($this->players[0])->create();

        expect(AuditLog::where('subject_type', 'subscriptions.fee')->value('organisation_id'))->toBe($this->club->id);
    });
});

describe('listing', function () {
    test('the ledger filters by status and totals the money', function () {
        Fee::factory()->for($this->players[0])->create(['amount_pence' => 1000]);
        Fee::factory()->for($this->players[1])->overdue()->create(['amount_pence' => 2000]);
        Fee::factory()->for($this->players[2])->paid()->create(['amount_pence' => 4000]);

        $this->actingAs($this->admin)->get(route('subscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('subscriptions/pages/Index')
                ->where('filter', 'due')
                ->has('fees', 2)
                ->where('totals', ['outstanding_pence' => 3000, 'overdue_pence' => 2000, 'collected_pence' => 4000]));

        $this->get(route('subscriptions.index', ['status' => 'overdue']))
            ->assertInertia(fn (Assert $page) => $page->has('fees', 1)->where('fees.0.overdue', true));

        $this->get(route('subscriptions.index', ['status' => 'paid']))
            ->assertInertia(fn (Assert $page) => $page->has('fees', 1)->where('fees.0.amount_pence', 4000));
    });

    test('workers cannot see fees', function () {
        $this->actingAs($this->coach)->get(route('subscriptions.index'))->assertForbidden();
    });
});

describe('payments', function () {
    test('taking a payment goes through the gateway and records the reference', function () {
        $fee = Fee::factory()->for($this->players[0])->create();

        $this->actingAs($this->admin)->post(route('subscriptions.fees.pay', $fee))->assertSessionHasNoErrors();

        expect($fee->refresh()->isPaid())->toBeTrue()
            ->and($fee->payment_reference)->toStartWith('fake_');
    });

    test('a declined payment leaves the fee unpaid and explains why', function () {
        $fee = Fee::factory()->for($this->players[0])->create();
        app(PaymentGateway::class)->declineNext('Insufficient funds');

        $this->actingAs($this->admin)
            ->post(route('subscriptions.fees.pay', $fee))
            ->assertSessionHasErrors(['payment' => 'Payment failed: Insufficient funds']);

        expect($fee->refresh()->isPaid())->toBeFalse();
    });

    test('a paid fee cannot be paid again or removed', function () {
        $fee = Fee::factory()->for($this->players[0])->paid()->create();

        $this->actingAs($this->admin);
        $this->post(route('subscriptions.fees.pay', $fee))->assertForbidden();
        $this->delete(route('subscriptions.fees.destroy', $fee))->assertForbidden();
    });

    test('another club\'s fee is a 404', function () {
        auth()->logout();
        $foreignFee = Fee::factory()->for(Player::factory()->for(Team::factory()->for(Organisation::factory())))->create();

        $this->actingAs($this->admin)->post(route('subscriptions.fees.pay', $foreignFee))->assertNotFound();
    });
});

describe('overdue check', function () {
    test('each overdue fee is announced once with a DTO', function () {
        Event::fake([PaymentOverdue::class]);
        $overdue = Fee::factory()->for($this->players[0])->overdue()->create();
        Fee::factory()->for($this->players[1])->create();
        Fee::factory()->for($this->players[2])->overdue()->paid()->create();

        $this->artisan('subscriptions:check-overdue')->assertSuccessful();
        $this->artisan('subscriptions:check-overdue')->assertSuccessful();

        Event::assertDispatchedTimes(PaymentOverdue::class, 1);
        Event::assertDispatched(PaymentOverdue::class, fn (PaymentOverdue $event) => $event->payment instanceof OverduePayment
            && $event->payment->feeId === $overdue->id
            && $event->payment->organisationId === $this->club->id
            && $event->payment->teamId === $this->team->id);
    });

    test('clubs without the module are skipped', function () {
        Event::fake([PaymentOverdue::class]);
        $otherClub = Organisation::factory()->create();
        Fee::factory()->for(Player::factory()->for(Team::factory()->for($otherClub)))->overdue()->create();

        $this->artisan('subscriptions:check-overdue')->assertSuccessful();

        Event::assertNotDispatched(PaymentOverdue::class);
    });

    test('the check runs daily', function () {
        $this->artisan('schedule:list')->expectsOutputToContain('subscriptions:check-overdue');
    });
});
