<?php

declare(strict_types=1);
use Modules\Availability\AvailabilityModule;
use Modules\Fixtures\FixturesModule;
use Modules\KitShop\KitShopModule;
use Modules\MatchReports\MatchReportsModule;
use Modules\Subscriptions\SubscriptionsModule;

return [

    /*
    |--------------------------------------------------------------------------
    | Installed modules
    |--------------------------------------------------------------------------
    |
    | Each entry is a module's definition class (modules/{Name}/{Name}Module.php).
    | Listing it installs the module: its service provider, routes and
    | migrations are registered. Removing the line uninstalls it, and the rest
    | of the application keeps working.
    |
    | Installed is not enabled. Which clubs can use an installed module is a
    | per-organisation feature flag, toggled by the super admin.
    |
    | Order does not matter: modules are registered dependencies first, and a
    | module whose hard dependency is missing stops the application booting.
    |
    */

    'installed' => [
        FixturesModule::class,
        AvailabilityModule::class,
        MatchReportsModule::class,
        SubscriptionsModule::class,
        KitShopModule::class,
    ],

];
