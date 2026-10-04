<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Payments;

/**
 * How Subscriptions takes money. Internal to the module: no other module
 * charges cards, so this lives outside Contracts/.
 *
 * Swapping the fake for a real provider means one new class implementing
 * this interface and one binding in SubscriptionsServiceProvider.
 */
interface PaymentGateway
{
    public function charge(int $amountPence, string $description): PaymentResult;
}
