<?php

declare(strict_types=1);

namespace Modules\KitShop;

use App\Modules\ModuleDefinition;
use Modules\KitShop\Database\Seeders\KitShopSeeder;

/**
 * KitShop: club kit, sizes, stock and orders for players.
 *
 * Soft-depends on Subscriptions. It works on its own; when a club also has
 * Subscriptions enabled, orders are limited to paid-up members. That check
 * goes through Core's MembershipStatus capability, so this module never
 * imports Subscriptions and can be enabled without it.
 */
final class KitShopModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'kit-shop';
    }

    public function name(): string
    {
        return 'Kit Shop';
    }

    public function description(): string
    {
        return 'Club kit, sizes, stock and orders.';
    }

    public function softDependencies(): array
    {
        return ['subscriptions'];
    }

    public function serviceProvider(): string
    {
        return KitShopServiceProvider::class;
    }

    public function seeder(): string
    {
        return KitShopSeeder::class;
    }
}
