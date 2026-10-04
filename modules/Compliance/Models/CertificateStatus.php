<?php

declare(strict_types=1);

namespace Modules\Compliance\Models;

use Carbon\CarbonImmutable;

/**
 * Worked out from the dates, never stored, so it is always correct today.
 * Ordered from worst to best so "the worst status" is a simple comparison.
 */
enum CertificateStatus: string
{
    case Missing = 'missing';
    case Expired = 'expired';
    case Expiring = 'expiring';
    case Valid = 'valid';

    /** A certificate expiring within this many days needs renewing now. */
    public const int EXPIRING_WITHIN_DAYS = 60;

    public static function for(CarbonImmutable $expiresOn): self
    {
        $today = CarbonImmutable::today();

        return match (true) {
            $expiresOn->isBefore($today) => self::Expired,
            $expiresOn->isBefore($today->addDays(self::EXPIRING_WITHIN_DAYS)) => self::Expiring,
            default => self::Valid,
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Missing => 0,
            self::Expired => 1,
            self::Expiring => 2,
            self::Valid => 3,
        };
    }
}
