<?php

declare(strict_types=1);

namespace Modules\Fixtures\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Fixtures\Models\Fixture;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    protected $model = Fixture::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            // Always the team's organisation; the scope is bypassed because factories often run with no current organisation
            'organisation_id' => fn (array $attributes) => Team::withoutOrganisationScope()->whereKey($attributes['team_id'])->value('organisation_id'),
            'opponent' => fake()->city().' '.fake()->randomElement(['Town', 'United', 'Athletic', 'Rangers']),
            'kick_off_at' => fake()->dateTimeBetween('+1 day', '+6 weeks')->format('Y-m-d 10:00:00'),
            'is_home' => fake()->boolean(),
            'competition' => fake()->randomElement(['League', 'Cup', 'Friendly', null]),
        ];
    }

    public function played(): static
    {
        return $this->state(fn () => [
            'kick_off_at' => fake()->dateTimeBetween('-6 weeks', '-1 day')->format('Y-m-d 10:00:00'),
        ]);
    }
}
