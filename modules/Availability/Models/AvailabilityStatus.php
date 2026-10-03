<?php

declare(strict_types=1);

namespace Modules\Availability\Models;

enum AvailabilityStatus: string
{
    case Available = 'available';
    case Maybe = 'maybe';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Maybe => 'Maybe',
            self::Unavailable => 'Unavailable',
        };
    }
}
