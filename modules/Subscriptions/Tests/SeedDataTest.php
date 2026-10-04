<?php

declare(strict_types=1);

use App\Models\Organisation;
use Modules\Subscriptions\Models\Fee;

beforeEach(fn () => $this->seed());

test('clubs with subscriptions have two overdue members, already announced, and everyone else paid', function () {
    foreach (['riverside-juniors-fc', 'hillcrest-ladies'] as $slug) {
        $club = Organisation::where('slug', $slug)->sole();
        $fees = Fee::withoutOrganisationScope()->where('organisation_id', $club->id)->where('description', '2026/27 season subs')->get();

        $overdue = $fees->filter(fn (Fee $fee) => $fee->isOverdue());

        expect($overdue)->toHaveCount(2)
            ->and($overdue->every(fn (Fee $fee) => $fee->overdue_notified_at !== null))->toBeTrue()
            ->and($fees->filter(fn (Fee $fee) => $fee->isPaid()))->toHaveCount($fees->count() - 2)
            ->and($fees->whereNotNull('paid_at')->every(fn (Fee $fee) => str_starts_with((string) $fee->payment_reference, 'fake_')))->toBeTrue();
    }
});

test('a training levy is due but not yet overdue', function () {
    $levies = Fee::withoutOrganisationScope()->where('description', 'Winter training levy')->get();

    expect($levies)->not->toBeEmpty()
        ->and($levies->every(fn (Fee $fee) => ! $fee->isPaid() && ! $fee->isOverdue()))->toBeTrue();
});
