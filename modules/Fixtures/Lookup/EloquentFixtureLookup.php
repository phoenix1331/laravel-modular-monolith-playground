<?php

declare(strict_types=1);

namespace Modules\Fixtures\Lookup;

use Illuminate\Database\Eloquent\Builder;
use Modules\Fixtures\Contracts\FixtureData;
use Modules\Fixtures\Contracts\FixtureLookup;
use Modules\Fixtures\Models\Fixture;

/**
 * Internal implementation of the public FixtureLookup contract.
 *
 * Fixture uses BelongsToOrganisation, so every query here is limited to the
 * current organisation without saying so. Results leave as FixtureData; the
 * Eloquent model never crosses the module boundary.
 */
final class EloquentFixtureLookup implements FixtureLookup
{
    public function find(int $fixtureId): ?FixtureData
    {
        return $this->query()->find($fixtureId)?->toData();
    }

    public function forTeam(int $teamId): array
    {
        return $this->toData($this->query()->where('team_id', $teamId)->orderBy('kick_off_at'));
    }

    public function upcomingForTeam(int $teamId, int $limit = 10): array
    {
        return $this->toData(
            $this->query()
                ->where('team_id', $teamId)
                ->where('kick_off_at', '>=', now())
                ->orderBy('kick_off_at')
                ->limit($limit),
        );
    }

    /**
     * @return Builder<Fixture>
     */
    private function query(): Builder
    {
        return Fixture::query()->with(['team', 'venue']);
    }

    /**
     * @param  Builder<Fixture>  $query
     * @return list<FixtureData>
     */
    private function toData(Builder $query): array
    {
        return array_values($query->get()->map(fn (Fixture $fixture) => $fixture->toData())->all());
    }
}
