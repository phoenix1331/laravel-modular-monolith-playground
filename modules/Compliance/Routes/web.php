<?php

declare(strict_types=1);

/*
| Compliance routes, wrapped by ModuleServiceProvider in the club area and
| `module:compliance`.
*/

use Illuminate\Support\Facades\Route;
use Modules\Compliance\Http\Controllers\CertificateController;

Route::prefix('compliance')->name('compliance.')->group(function () {
    Route::get('/', [CertificateController::class, 'index'])->name('index');
    Route::post('certificates', [CertificateController::class, 'store'])->name('certificates.store');
    Route::delete('certificates/{certificate}', [CertificateController::class, 'destroy'])->name('certificates.destroy');
});
