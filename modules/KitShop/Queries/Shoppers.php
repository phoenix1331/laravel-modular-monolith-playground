<?php

declare(strict_types=1);

namespace Modules\KitShop\Queries;

use App\Capabilities\MembershipStatus;
use App\Models\Player;
use App\Models\User;
use App\Queries\VisibleTeams;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\KitShop\Models\Order;

/**
 * Who the user can order kit for, and the orders they can see.
 *
 * Membership flags come from the MembershipStatus capability, so the page can
 * warn before an order is refused. With Subscriptions off for the club, the
 * null answer marks everyone as paid up and the page shows no warnings.
 */
final class Shoppers
{
    public function __construct(
        private readonly VisibleTeams $teams,
        private readonly MembershipStatus $membership,
    ) {}

    /**
     * @return Collection<int, array{id: int, name: string, team: string, paid_up: bool}>
     */
    public function players(User $user): Collection
    {
        return Player::query()
            ->with('team:id,name')
            ->whereIn('team_id', $this->teams->for($user)->select('teams.id'))
            ->orderBy('last_name')
            ->get()
            ->map(fn (Player $player) => [
                'id' => $player->id,
                'name' => $player->full_name,
                'team' => $player->team->name,
                'paid_up' => $this->membership->isPaidUp($player->id),
            ]);
    }

    public function player(int $playerId): Player
    {
        return Player::findOrFail($playerId);
    }

    /**
     * @return Collection<int, Order>
     */
    public function orders(User $user): Collection
    {
        return Order::query()
            ->with(['player:id,first_name,last_name', 'product:id,name'])
            ->whereHas('player', fn (Builder $players) => $players->whereIn('team_id', $this->teams->for($user)->select('teams.id')))
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function tracksMembership(): bool
    {
        return $this->membership->tracksMembership();
    }
}
