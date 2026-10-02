<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    AuditServiceProvider::class,
    FortifyServiceProvider::class,
    ModulesServiceProvider::class,
];
