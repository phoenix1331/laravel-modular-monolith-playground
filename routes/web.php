<?php

declare(strict_types=1);

use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Platform\OrganisationController;
use App\Http\Controllers\Platform\OrganisationModuleController;
use App\Http\Controllers\Teams\PlayerController;
use App\Http\Controllers\Teams\TeamController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// No landing page: guests go to the login form, signed-in users to their own area
Route::get('/', function (Request $request): RedirectResponse {
    /** @var User|null $user */
    $user = $request->user();

    return $user === null ? to_route('login') : redirect($user->homeUrl());
})->name('home');

// Club area: admins and workers, always inside their own organisation
Route::middleware(['auth', 'role:admin,worker'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('teams', TeamController::class);
    Route::resource('teams.players', PlayerController::class)
        ->shallow()
        ->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
});

// Platform area: the super admin only, across every organisation
Route::middleware(['auth', 'role:super_admin'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', [OrganisationController::class, 'index'])->name('dashboard');
    Route::get('organisations/{organisation}', [OrganisationController::class, 'show'])->name('organisations.show');
    Route::put('organisations/{organisation}/modules/{module}', [OrganisationModuleController::class, 'update'])->name('organisations.modules.update');
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
});

require __DIR__.'/settings.php';
