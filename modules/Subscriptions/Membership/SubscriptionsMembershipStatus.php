<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Membership;

use App\Capabilities\MembershipStatus;
use Modules\Subscriptions\Models\Fee;

/**
 * Subscriptions' answer to Core's MembershipStatus capability: a player is
 * paid up unless they have a fee that is unpaid and past its due date.
 *
 * Internal to Subscriptions. Other modules only ever see the Core interface.
 */
final class SubscriptionsMembershipStatus implements MembershipStatus
{
    public function tracksMembership(): bool
    {
        return true;
    }

    public function isPaidUp(int $playerId): bool
    {
        return ! Fee::query()->overdue()->where('player_id', $playerId)->exists();
    }
}
