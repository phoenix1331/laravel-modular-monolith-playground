<?php

declare(strict_types=1);

namespace Modules\KitShop\Database\Seeders;

use App\Capabilities\MembershipStatus;
use App\Models\Organisation;
use App\Models\Player;
use App\Models\Team;
use App\Modules\ModuleManager;
use App\Tenancy\CurrentOrganisation;
use Illuminate\Database\Seeder;
use Modules\KitShop\Models\Order;
use Modules\KitShop\Models\Product;
use Modules\KitShop\Models\Stock;

/**
 * A club shop for every club with KitShop enabled: a small range in junior
 * and adult sizes, one size sold out, and a couple of orders per team, the
 * first of which has already been handed over.
 *
 * Orders are only placed for players MembershipStatus says are paid up,
 * the same rule PlaceOrder enforces. With Subscriptions on, the overdue
 * players are skipped; with it off (or uninstalled), Core's null answer lets
 * everyone order. KitShop never learns which module, if any, answered.
 * DatabaseSeeder runs this after SubscriptionsSeeder (soft dependency).
 */
final class KitShopSeeder extends Seeder
{
    /** name => [price in pence, stock per size] */
    private const array RANGE = [
        'Home shirt' => [2500, ['YS' => 8, 'YM' => 10, 'YL' => 6, 'S' => 4, 'M' => 4]],
        'Away shirt' => [2500, ['YS' => 5, 'YM' => 6, 'YL' => 5, 'S' => 3, 'M' => 3]],
        'Training top' => [1800, ['YS' => 6, 'YM' => 0, 'YL' => 4, 'S' => 5, 'M' => 5]],
        'Shorts' => [1200, ['YS' => 10, 'YM' => 12, 'YL' => 10, 'S' => 6, 'M' => 6]],
        'Socks' => [600, ['YS' => 15, 'YM' => 15, 'YL' => 15, 'S' => 10, 'M' => 10]],
    ];

    public function run(ModuleManager $modules, CurrentOrganisation $current): void
    {
        foreach (Organisation::query()->orderBy('id')->get() as $organisation) {
            if ($modules->isEnabled($organisation, 'kit-shop')) {
                // Resolved inside runAs: the Subscriptions binding checks the
                // current club's flag on every resolve
                $current->runAs($organisation, fn () => $this->seedClub($organisation, app(MembershipStatus::class)));
            }
        }
    }

    private function seedClub(Organisation $organisation, MembershipStatus $membership): void
    {
        $products = [];

        foreach (self::RANGE as $name => [$price, $sizes]) {
            $products[] = $product = new Product(['name' => $name, 'price_pence' => $price]);
            $product->organisation_id = $organisation->id;
            $product->save();

            foreach ($sizes as $size => $quantity) {
                $stock = new Stock(['product_id' => $product->id, 'size' => $size, 'quantity' => $quantity]);
                $stock->organisation_id = $organisation->id;
                $stock->save();
            }
        }

        $teams = Team::query()->where('organisation_id', $organisation->id)->with(['players', 'workers'])->orderBy('id')->get();

        foreach ($teams as $team) {
            $buyers = $team->players
                ->sortBy('shirt_number')
                ->filter(fn (Player $player) => $membership->isPaidUp($player->id))
                ->take(2)
                ->values();

            foreach ($buyers as $index => $player) {
                $this->order($player, $products[$index], $team, fulfilled: $index === 0);
            }
        }
    }

    private function order(Player $player, Product $product, Team $team, bool $fulfilled): void
    {
        $size = str_starts_with($team->age_group, 'U') ? 'YM' : 'M';

        $order = new Order([
            'player_id' => $player->id,
            'product_id' => $product->id,
            'size' => $size,
            'quantity' => 1,
            'total_pence' => $product->price_pence,
            'ordered_by' => $team->workers->first()?->id,
        ]);
        $order->organisation_id = (int) $player->organisation_id;
        $order->fulfilled_at = $fulfilled ? now()->subDays(3) : null;
        $order->save();
    }
}
