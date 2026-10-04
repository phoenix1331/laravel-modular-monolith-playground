<?php

declare(strict_types=1);

use App\Models\Organisation;
use Modules\Messaging\Models\Announcement;

beforeEach(fn () => $this->seed());

test('Riverside\'s board has hand-written posts', function () {
    $riverside = Organisation::where('slug', 'riverside-juniors-fc')->sole();
    $manual = Announcement::withoutOrganisationScope()->where('organisation_id', $riverside->id)->where('source', 'manual')->get();

    expect($manual->whereNull('team_id'))->toHaveCount(2)
        ->and($manual->whereNotNull('team_id'))->toHaveCount(4);
});

test('seeded upcoming fixtures reach the board through FixtureScheduled', function () {
    $this->skipUnlessModuleInstalled('fixtures');

    expect(Announcement::withoutOrganisationScope()->where('source', 'fixtures')->count())->toBe(12);
});

test('seeded overdue fees reach the board through PaymentOverdue, for admins only', function () {
    $this->skipUnlessModuleInstalled('subscriptions');

    $reminders = Announcement::withoutOrganisationScope()->where('source', 'subscriptions')->get();

    expect($reminders)->toHaveCount(2)
        ->and($reminders->every(fn (Announcement $announcement) => $announcement->audience === Announcement::AUDIENCE_ADMINS))->toBeTrue();
});

test('clubs without messaging get no posts, even when their modules fire events', function () {
    $others = Organisation::where('slug', '!=', 'riverside-juniors-fc')->pluck('id');

    expect(Announcement::withoutOrganisationScope()->whereIn('organisation_id', $others)->count())->toBe(0);
});
