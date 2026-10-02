<?php

declare(strict_types=1);

namespace App\Modules\Exceptions;

use OutOfBoundsException;

final class UnknownModule extends OutOfBoundsException
{
    public static function key(string $key): self
    {
        return new self("No installed module has the key [{$key}].");
    }
}
