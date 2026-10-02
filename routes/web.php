<?php

declare(strict_types=1);

use App\Http\Controllers\Teams\PlayerController;
use App\Http\Controllers\Teams\TeamController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Club area: admins and workers, always inside their own organisation
Route::middleware(['auth', 'role:admin,worker'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('teams', TeamController::class);
    Route::resource('teams.players', PlayerController::class)
        ->shallow()
        ->only(['create', 'store', 'edit', 'update', 'destroy']);
});

// Platform area: the super admin only, across every organisation
Route::middleware(['auth', 'role:super_admin'])->prefix('platform')->name('platform.')->group(function () {
    Route::inertia('/', 'platform/Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
