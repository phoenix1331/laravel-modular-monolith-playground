<?php

declare(strict_types=1);

namespace Modules\Fixtures\Contracts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Fixtures\Contracts\FixtureData;

/**
 * Dispatched after a fixture is scheduled.
 *
 * Other modules react to this without Fixtures knowing they exist: Messaging
 * posts a team announcement, for example. Listeners receive FixtureData, not
 * the model, for the same reason the lookup does.
 *
 * Public: part of Fixtures' Contracts layer.
 */
final class FixtureScheduled
{
    use Dispatchable;

    public function __construct(public readonly FixtureData $fixture) {}
}
