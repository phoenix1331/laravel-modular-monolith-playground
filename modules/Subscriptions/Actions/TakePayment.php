<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Actions;

use Modules\Subscriptions\Models\Fee;
use Modules\Subscriptions\Payments\PaymentGateway;
use Modules\Subscriptions\Payments\PaymentResult;

/**
 * Charges a fee through the gateway and records the outcome. A declined
 * charge leaves the fee unpaid and returns the reason.
 */
final class TakePayment
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function handle(Fee $fee): PaymentResult
    {
        if ($fee->isPaid()) {
            return PaymentResult::failed('Already paid.');
        }

        $result = $this->gateway->charge($fee->amount_pence, $fee->description);

        if ($result->successful) {
            $fee->forceFill(['paid_at' => now(), 'payment_reference' => $result->reference])->save();
        }

        return $result;
    }
}
