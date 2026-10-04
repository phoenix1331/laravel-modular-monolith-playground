<?php

declare(strict_types=1);

namespace Modules\KitShop;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleServiceProvider;

/**
 * Nothing to bind. KitShop *consumes* the MembershipStatus capability; Core
 * or Subscriptions decides which implementation it gets.
 */
final class KitShopServiceProvider extends ModuleServiceProvider
{
    protected function definition(): ModuleDefinition
    {
        return new KitShopModule;
    }
}
