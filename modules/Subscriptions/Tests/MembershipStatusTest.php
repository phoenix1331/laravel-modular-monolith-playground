<?php

declare(strict_types=1);

use App\Capabilities\MembershipStatus;
use App\Capabilities\NullMembershipStatus;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Modules\Subscriptions\Membership\SubscriptionsMembershipStatus;
use Modules\Subscriptions\Models\Fee;

/*
| Subscriptions' side of the soft dependency: it answers Core's
| MembershipStatus for clubs that have it enabled, and steps aside (null
| answer) for clubs that do not, decided on every resolve.
*/

beforeEach(function () {
    $this->withModule = Organisation::factory()->create();
    $this->withoutModule = Organisation::factory()->create();
    app(ModuleManager::class)->enable($this->withModule, 'subscriptions');

    $this->paidUp = Player::factory()->for(Team::factory()->for($this->withModule))->create();
    $this->behind = Player::factory()->for(Team::factory()->for($this->withModule))->create();
    Fee::factory()->for($this->paidUp)->paid()->overdue()->create();
    Fee::factory()->for($this->paidUp)->create();
    Fee::factory()->for($this->behind)->overdue()->create();
});

test('a club with subscriptions gets real answers', function () {
    $this->actingAs(User::factory()->admin()->forOrganisation($this->withModule)->create());
    $status = app(MembershipStatus::class);

    expect($status)->toBeInstanceOf(SubscriptionsMembershipStatus::class)
        ->and($status->tracksMembership())->toBeTrue()
        ->and($status->isPaidUp($this->paidUp->id))->toBeTrue()
        ->and($status->isPaidUp($this->behind->id))->toBeFalse();
});

test('a club without subscriptions gets the null answer, even with the module installed', function () {
    $this->actingAs(User::factory()->admin()->forOrganisation($this->withoutModule)->create());

    expect(app(MembershipStatus::class))->toBeInstanceOf(NullMembershipStatus::class);
});

test('switching subscriptions off switches the answer off immediately', function () {
    $this->actingAs(User::factory()->admin()->forOrganisation($this->withModule)->create());
    app(ModuleManager::class)->disable($this->withModule, 'subscriptions');

    expect(app(MembershipStatus::class)->tracksMembership())->toBeFalse()
        ->and(app(MembershipStatus::class)->isPaidUp($this->behind->id))->toBeTrue();
});

test('the answer follows the current organisation, not the first one resolved', function () {
    $current = app(CurrentOrganisation::class);

    $inClub = $current->runAs($this->withModule, fn () => app(MembershipStatus::class)->tracksMembership());
    $elsewhere = $current->runAs($this->withoutModule, fn () => app(MembershipStatus::class)->tracksMembership());

    expect($inClub)->toBeTrue()->and($elsewhere)->toBeFalse();
});
