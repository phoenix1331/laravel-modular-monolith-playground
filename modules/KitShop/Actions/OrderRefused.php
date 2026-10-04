<?php

declare(strict_types=1);

namespace Modules\KitShop\Actions;

use DomainException;

final class OrderRefused extends DomainException
{
    public static function notPaidUp(string $player): self
    {
        return new self("{$player} has overdue subscriptions. Orders are limited to paid-up members.");
    }

    public static function outOfStock(string $product, string $size, int $left): self
    {
        return new self("Only {$left} {$product} left in size {$size}.");
    }
}
