<?php

declare(strict_types=1);

namespace Modules\KitShop\Actions;

use App\Capabilities\MembershipStatus;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\KitShop\Models\Order;
use Modules\KitShop\Models\Product;
use Modules\KitShop\Models\Stock;

/**
 * Places an order for a player, taking it out of stock.
 *
 * The soft dependency, consumer side: membership is checked through Core's
 * MembershipStatus capability. If the club has Subscriptions enabled, that is
 * Subscriptions answering; otherwise it is Core's null answer, which never
 * refuses. This class cannot tell which, and does not need to.
 */
final class PlaceOrder
{
    public function __construct(private readonly MembershipStatus $membership) {}

    public function handle(Player $player, Product $product, string $size, int $quantity, User $by): Order
    {
        if ($this->membership->tracksMembership() && ! $this->membership->isPaidUp($player->id)) {
            throw OrderRefused::notPaidUp($player->full_name);
        }

        return DB::transaction(function () use ($player, $product, $size, $quantity, $by): Order {
            // Lock the row so two orders cannot both take the last item
            $stock = Stock::query()
                ->where('product_id', $product->id)
                ->where('size', $size)
                ->lockForUpdate()
                ->firstOrFail();

            if ($stock->quantity < $quantity) {
                throw OrderRefused::outOfStock($product->name, $size, $stock->quantity);
            }

            $stock->decrement('quantity', $quantity);

            return Order::create([
                'player_id' => $player->id,
                'product_id' => $product->id,
                'size' => $size,
                'quantity' => $quantity,
                'total_pence' => $product->price_pence * $quantity,
                'ordered_by' => $by->id,
            ]);
        });
    }
}
