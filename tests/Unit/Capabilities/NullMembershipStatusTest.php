<?php

declare(strict_types=1);

use App\Capabilities\NullMembershipStatus;

test('with no module tracking membership, nobody is held back', function () {
    $status = new NullMembershipStatus;

    expect($status->tracksMembership())->toBeFalse()
        ->and($status->isPaidUp(1))->toBeTrue()
        ->and($status->isPaidUp(999))->toBeTrue();
});
