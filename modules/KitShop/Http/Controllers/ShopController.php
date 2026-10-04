<?php

declare(strict_types=1);

namespace Modules\KitShop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\KitShop\Actions\OrderRefused;
use Modules\KitShop\Actions\PlaceOrder;
use Modules\KitShop\Http\Requests\PlaceOrderRequest;
use Modules\KitShop\Models\Order;
use Modules\KitShop\Models\Product;
use Modules\KitShop\Models\Stock;
use Modules\KitShop\Queries\Catalogue;
use Modules\KitShop\Queries\Shoppers;

class ShopController extends Controller
{
    public function index(Request $request, Catalogue $catalogue, Shoppers $shoppers): Response
    {
        Gate::authorize('viewAny', Order::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('kit-shop/pages/Shop', [
            'products' => $catalogue->products()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price_pence' => $product->price_pence,
                'stock' => $product->stock->map(fn (Stock $stock) => ['size' => $stock->size, 'quantity' => $stock->quantity])->values(),
            ])->values(),
            'players' => $shoppers->players($user)->values(),
            'orders' => $shoppers->orders($user)->map(fn (Order $order) => [
                'id' => $order->id,
                'player' => $order->player->full_name,
                'product' => $order->product->name,
                'size' => $order->size,
                'quantity' => $order->quantity,
                'total_pence' => $order->total_pence,
                'fulfilled' => $order->fulfilled_at !== null,
                'can_fulfil' => $user->can('fulfil', $order),
            ])->values(),
            'membershipTracked' => $shoppers->tracksMembership(),
            'can' => ['manageProducts' => $user->can('manage', Product::class)],
        ]);
    }

    public function store(PlaceOrderRequest $request, PlaceOrder $place, Catalogue $catalogue, Shoppers $shoppers): RedirectResponse
    {
        $player = $shoppers->player($request->integer('player_id'));
        Gate::authorize('create', [Order::class, $player]);

        /** @var User $user */
        $user = $request->user();

        try {
            $place->handle(
                $player,
                $catalogue->product($request->integer('product_id')),
                strtoupper((string) $request->validated('size')),
                $request->integer('quantity'),
                $user,
            );
        } catch (OrderRefused $refused) {
            throw ValidationException::withMessages(['order' => $refused->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order placed.')]);

        return to_route('kit-shop.index');
    }

    public function fulfil(Order $order): RedirectResponse
    {
        Gate::authorize('fulfil', $order);

        $order->forceFill(['fulfilled_at' => now()])->save();

        return back();
    }
}
