<?php

declare(strict_types=1);

/*
| Availability routes, wrapped by ModuleServiceProvider in the club area and
| `module:availability`. A club only reaches these once both Fixtures and
| Availability are enabled, because Availability cannot be enabled first.
*/

use Illuminate\Support\Facades\Route;
use Modules\Availability\Http\Controllers\AvailabilityController;

Route::prefix('availability')->name('availability.')->group(function () {
    Route::get('/', [AvailabilityController::class, 'index'])->name('index');
    Route::get('fixtures/{fixture}', [AvailabilityController::class, 'show'])->whereNumber('fixture')->name('show');
    Route::put('fixtures/{fixture}/players/{player}', [AvailabilityController::class, 'update'])->whereNumber('fixture')->name('update');
});
