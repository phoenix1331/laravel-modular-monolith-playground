<?php

declare(strict_types=1);

/*
| Fixtures routes. ModuleServiceProvider wraps this file in the club area
| middleware plus `module:fixtures`, so nothing here repeats it. Route names
| are prefixed with the module key to avoid collisions.
*/

use Illuminate\Support\Facades\Route;
use Modules\Fixtures\Http\Controllers\FixtureController;
use Modules\Fixtures\Http\Controllers\VenueController;

Route::prefix('fixtures')->name('fixtures.')->group(function () {
    Route::get('venues', [VenueController::class, 'index'])->name('venues.index');
    Route::post('venues', [VenueController::class, 'store'])->name('venues.store');
    Route::delete('venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy');

    Route::get('/', [FixtureController::class, 'index'])->name('index');
    Route::get('create', [FixtureController::class, 'create'])->name('create');
    Route::post('/', [FixtureController::class, 'store'])->name('store');
    Route::get('{fixture}/edit', [FixtureController::class, 'edit'])->name('edit');
    Route::put('{fixture}', [FixtureController::class, 'update'])->name('update');
    Route::delete('{fixture}', [FixtureController::class, 'destroy'])->name('destroy');
});
