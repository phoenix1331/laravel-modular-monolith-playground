<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('demo', fn () => 'demo module')->name('demo.index');
