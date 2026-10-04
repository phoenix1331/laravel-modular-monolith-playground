<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Contracts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Subscriptions\Contracts\OverduePayment;

/**
 * Dispatched once per fee, the first time the daily check finds it overdue.
 * Messaging posts a reminder; Subscriptions does not know who listens.
 *
 * Public: part of Subscriptions' Contracts layer.
 */
final class PaymentOverdue
{
    use Dispatchable;

    public function __construct(public readonly OverduePayment $payment) {}
}
