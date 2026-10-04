<?php

declare(strict_types=1);

namespace Modules\KitShop\Queries;

use Illuminate\Support\Collection;
use Modules\KitShop\Models\Product;

final class Catalogue
{
    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return Product::query()->with(['stock' => fn ($stock) => $stock->orderBy('id')])->orderBy('name')->get();
    }

    public function product(int $productId): Product
    {
        return Product::findOrFail($productId);
    }
}
