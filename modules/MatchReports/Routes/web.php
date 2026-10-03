<?php

declare(strict_types=1);

/*
| Match Reports routes, wrapped by ModuleServiceProvider in the club area and
| `module:match-reports`.
*/

use Illuminate\Support\Facades\Route;
use Modules\MatchReports\Http\Controllers\MatchReportController;

Route::prefix('match-reports')->name('match-reports.')->group(function () {
    Route::get('/', [MatchReportController::class, 'index'])->name('index');
    Route::get('fixtures/{fixture}', [MatchReportController::class, 'show'])->whereNumber('fixture')->name('show');
    Route::put('fixtures/{fixture}', [MatchReportController::class, 'update'])->whereNumber('fixture')->name('update');
});
