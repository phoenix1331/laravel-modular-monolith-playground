<?php

declare(strict_types=1);

namespace Modules\Fixtures\Contracts;

use Carbon\CarbonImmutable;

/**
 * A fixture as other modules see it: plain, read-only data.
 *
 * Other modules never receive the Fixture model. A model would let them
 * lazy-load relations, call save() or depend on columns, which ties them to
 * Fixtures' internals. This DTO is the agreed shape; the internals can change
 * freely as long as they can still produce it.
 *
 * Public: part of Fixtures' Contracts layer.
 */
final readonly class FixtureData
{
    public function __construct(
        public int $id,
        public int $organisationId,
        public int $teamId,
        public string $teamName,
        public string $opponent,
        public CarbonImmutable $kickOffAt,
        public bool $isHome,
        public ?string $venue,
        public ?string $competition,
    ) {}

    public function hasKickedOff(): bool
    {
        return $this->kickOffAt->isPast();
    }
}
