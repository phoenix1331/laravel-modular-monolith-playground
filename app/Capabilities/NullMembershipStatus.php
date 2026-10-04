<?php

declare(strict_types=1);

namespace App\Capabilities;

/**
 * The answer when no module tracks membership: nobody is held back.
 *
 * "Not tracked" deliberately means "allowed". A club without Subscriptions
 * should be able to use KitShop normally, not find every order blocked.
 */
final class NullMembershipStatus implements MembershipStatus
{
    public function tracksMembership(): bool
    {
        return false;
    }

    public function isPaidUp(int $playerId): bool
    {
        return true;
    }
}
