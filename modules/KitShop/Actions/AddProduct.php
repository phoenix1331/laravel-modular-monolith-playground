<?php

declare(strict_types=1);

namespace Modules\KitShop\Actions;

use Illuminate\Support\Facades\DB;
use Modules\KitShop\Models\Product;

final class AddProduct
{
    /**
     * @param  array<string, int>  $stockBySize  e.g. ['S' => 10, 'M' => 12]
     */
    public function handle(string $name, int $pricePence, array $stockBySize): Product
    {
        return DB::transaction(function () use ($name, $pricePence, $stockBySize): Product {
            $product = Product::create(['name' => $name, 'price_pence' => $pricePence]);

            foreach ($stockBySize as $size => $quantity) {
                $product->stock()->create(['size' => $size, 'quantity' => $quantity]);
            }

            return $product;
        });
    }
}
