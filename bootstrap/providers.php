<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AuditServiceProvider::class,
    FortifyServiceProvider::class,
];
