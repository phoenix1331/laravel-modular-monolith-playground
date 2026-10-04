<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Contracts;

use Carbon\CarbonImmutable;

/**
 * An unpaid fee past its due date, as other modules see it.
 *
 * Public: part of Subscriptions' Contracts layer.
 */
final readonly class OverduePayment
{
    public function __construct(
        public int $feeId,
        public int $organisationId,
        public int $playerId,
        public string $playerName,
        public int $teamId,
        public string $description,
        public int $amountPence,
        public CarbonImmutable $dueOn,
    ) {}
}
