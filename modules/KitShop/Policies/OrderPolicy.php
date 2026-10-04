<?php

declare(strict_types=1);

namespace Modules\KitShop\Policies;

use App\Models\Player;
use App\Models\User;
use Modules\KitShop\Models\Order;

/**
 * Anyone who can see a player's team can order kit for them; admins mark
 * orders as handed out.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isWorker();
    }

    public function create(User $user, Player $player): bool
    {
        return $user->can('view', $player->team);
    }

    public function fulfil(User $user, Order $order): bool
    {
        return $user->isAdmin() && $user->organisation_id === $order->organisation_id && $order->fulfilled_at === null;
    }
}
