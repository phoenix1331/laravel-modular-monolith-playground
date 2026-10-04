<?php

declare(strict_types=1);

/*
| Kit Shop routes, wrapped by ModuleServiceProvider in the club area and
| `module:kit-shop`.
*/

use Illuminate\Support\Facades\Route;
use Modules\KitShop\Http\Controllers\ProductController;
use Modules\KitShop\Http\Controllers\ShopController;

Route::prefix('kit-shop')->name('kit-shop.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::post('orders', [ShopController::class, 'store'])->name('orders.store');
    Route::post('orders/{order}/fulfil', [ShopController::class, 'fulfil'])->name('orders.fulfil');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::put('products/{product}/stock', [ProductController::class, 'stock'])->name('products.stock');
});
