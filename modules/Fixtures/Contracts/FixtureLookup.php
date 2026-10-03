<?php

declare(strict_types=1);

namespace Modules\Fixtures\Contracts;

/**
 * How other modules read fixtures. Availability and MatchReports type-hint
 * this interface; FixturesServiceProvider binds the implementation.
 *
 * Every method answers for the current organisation only. Another club's
 * fixture is indistinguishable from one that does not exist (null or absent).
 *
 * Public: part of Fixtures' Contracts layer.
 */
interface FixtureLookup
{
    public function find(int $fixtureId): ?FixtureData;

    /**
     * Every fixture for a team, earliest kick-off first.
     *
     * @return list<FixtureData>
     */
    public function forTeam(int $teamId): array;

    /**
     * Fixtures that have not kicked off yet, earliest first.
     *
     * @return list<FixtureData>
     */
    public function upcomingForTeam(int $teamId, int $limit = 10): array;
}
