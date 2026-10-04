<?php

declare(strict_types=1);

namespace Modules\Messaging\Listeners;

use Modules\Messaging\Models\Announcement;
use Modules\Subscriptions\Contracts\Events\PaymentOverdue;

/**
 * Reminds admins about an overdue fee. Admins only: who owes money is not
 * something to post on the team board.
 */
final class RemindOverduePayment
{
    use PostsForOrganisation;

    public function handle(PaymentOverdue $event): void
    {
        $payment = $event->payment;
        $amount = '£'.number_format($payment->amountPence / 100, 2);

        $this->postFor($payment->organisationId, [
            'team_id' => $payment->teamId,
            'audience' => Announcement::AUDIENCE_ADMINS,
            'source' => 'subscriptions',
            'title' => "Overdue: {$payment->playerName}",
            'body' => "{$payment->description} ({$amount}) was due on {$payment->dueOn->format('j F Y')}.",
        ]);
    }
}
