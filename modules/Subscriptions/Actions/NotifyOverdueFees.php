<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Actions;

use Modules\Subscriptions\Contracts\Events\PaymentOverdue;
use Modules\Subscriptions\Models\Fee;

/**
 * Announces each overdue fee once, for the current organisation.
 *
 * Marking `overdue_notified_at` makes the daily run idempotent: a fee that
 * stays unpaid for a month produces one PaymentOverdue, not thirty.
 */
final class NotifyOverdueFees
{
    public function handle(): int
    {
        $fees = Fee::query()->overdue()->whereNull('overdue_notified_at')->with('player')->get();

        foreach ($fees as $fee) {
            $fee->forceFill(['overdue_notified_at' => now()])->save();

            PaymentOverdue::dispatch($fee->toOverduePayment());
        }

        return $fees->count();
    }
}
