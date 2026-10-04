<?php

declare(strict_types=1);

/*
| Messaging routes, wrapped by ModuleServiceProvider in the club area and
| `module:messaging`.
*/

use Illuminate\Support\Facades\Route;
use Modules\Messaging\Http\Controllers\AnnouncementController;

Route::prefix('messages')->name('messaging.')->group(function () {
    Route::get('/', [AnnouncementController::class, 'index'])->name('index');
    Route::post('/', [AnnouncementController::class, 'store'])->name('store');
    Route::delete('{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
});
