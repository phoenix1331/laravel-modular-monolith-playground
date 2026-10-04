<?php

declare(strict_types=1);

/*
| Subscriptions routes, wrapped by ModuleServiceProvider in the club area and
| `module:subscriptions`. Admin-only rules live in FeePolicy.
*/

use Illuminate\Support\Facades\Route;
use Modules\Subscriptions\Http\Controllers\FeeController;

Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [FeeController::class, 'index'])->name('index');
    Route::post('fees', [FeeController::class, 'store'])->name('fees.store');
    Route::post('fees/{fee}/pay', [FeeController::class, 'pay'])->name('fees.pay');
    Route::delete('fees/{fee}', [FeeController::class, 'destroy'])->name('fees.destroy');
});
