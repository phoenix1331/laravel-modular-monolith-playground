<?php

declare(strict_types=1);

namespace Modules\Subscriptions;

use App\Modules\ModuleDefinition;
use Modules\Subscriptions\Database\Seeders\SubscriptionsSeeder;

/**
 * Subscriptions: membership fees per player, payments through a gateway, and
 * overdue tracking. Emits PaymentOverdue for other modules to react to.
 *
 * No dependencies. KitShop soft-depends on it to restrict orders to paid
 * members; Messaging listens to its public event.
 */
final class SubscriptionsModule extends ModuleDefinition
{
    public function key(): string
    {
        return 'subscriptions';
    }

    public function name(): string
    {
        return 'Subscriptions';
    }

    public function description(): string
    {
        return 'Membership fees, payments and overdue tracking.';
    }

    public function serviceProvider(): string
    {
        return SubscriptionsServiceProvider::class;
    }

    public function seeder(): string
    {
        return SubscriptionsSeeder::class;
    }
}
