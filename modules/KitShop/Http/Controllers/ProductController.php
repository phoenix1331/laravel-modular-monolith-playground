<?php

declare(strict_types=1);

namespace Modules\KitShop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\KitShop\Actions\AddProduct;
use Modules\KitShop\Http\Requests\AddProductRequest;
use Modules\KitShop\Http\Requests\SetStockRequest;
use Modules\KitShop\Models\Product;
use Modules\KitShop\Models\Stock;
use Modules\KitShop\Queries\Catalogue;

class ProductController extends Controller
{
    public function index(Catalogue $catalogue): Response
    {
        Gate::authorize('manage', Product::class);

        return Inertia::render('kit-shop/pages/Products', [
            'products' => $catalogue->products()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price_pence' => $product->price_pence,
                'stock' => $product->stock->map(fn (Stock $stock) => ['size' => $stock->size, 'quantity' => $stock->quantity])->values(),
            ])->values(),
        ]);
    }

    public function store(AddProductRequest $request, AddProduct $add): RedirectResponse
    {
        Gate::authorize('manage', Product::class);

        $add->handle((string) $request->validated('name'), $request->pricePence(), $request->stockBySize());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product added.')]);

        return to_route('kit-shop.products.index');
    }

    public function stock(SetStockRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product->stock()->updateOrCreate(
            ['size' => strtoupper((string) $request->validated('size'))],
            ['quantity' => $request->integer('quantity')],
        );

        return back();
    }
}
