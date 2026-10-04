<?php

declare(strict_types=1);

namespace Modules\KitShop\Policies;

use App\Models\User;
use Modules\KitShop\Models\Product;

/**
 * The kit range and stock levels are managed by club admins.
 */
class ProductPolicy
{
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() && $user->organisation_id === $product->organisation_id;
    }
}
