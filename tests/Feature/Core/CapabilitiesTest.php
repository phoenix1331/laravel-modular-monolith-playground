<?php

declare(strict_types=1);

use App\Capabilities\MembershipStatus;
use App\Capabilities\NullMembershipStatus;
use App\Models\User;

/*
| Core's side of soft dependencies: every capability resolves to something,
| whatever modules are installed. Which module answers is tested by that
| module; Core only guarantees there is always an answer.
*/

test('membership status always resolves', function () {
    expect(app(MembershipStatus::class))->toBeInstanceOf(MembershipStatus::class);
});

test('with no current organisation the null answer is used', function () {
    expect(app(MembershipStatus::class))->toBeInstanceOf(NullMembershipStatus::class);

    $this->actingAs(User::factory()->superAdmin()->create());

    expect(app(MembershipStatus::class))->toBeInstanceOf(NullMembershipStatus::class);
});
