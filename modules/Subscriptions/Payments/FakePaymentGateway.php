<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Payments;

use Illuminate\Support\Str;

/**
 * Approves every charge with a `fake_` reference, so the playground needs no
 * payment account. `declineNext()` makes the next charge fail, which is how
 * tests (and curious developers in tinker) exercise the failure path.
 */
final class FakePaymentGateway implements PaymentGateway
{
    private ?string $declineReason = null;

    public function declineNext(string $reason = 'Card declined'): void
    {
        $this->declineReason = $reason;
    }

    public function charge(int $amountPence, string $description): PaymentResult
    {
        if ($this->declineReason !== null) {
            $reason = $this->declineReason;
            $this->declineReason = null;

            return PaymentResult::failed($reason);
        }

        return PaymentResult::succeeded('fake_'.Str::lower(Str::random(16)));
    }
}
