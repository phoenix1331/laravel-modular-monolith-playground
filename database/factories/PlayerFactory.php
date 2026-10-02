<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            // A player always belongs to their team's organisation. The scope is bypassed because factories often run with no current organisation
            'organisation_id' => fn (array $attributes) => Team::withoutOrganisationScope()->whereKey($attributes['team_id'])->value('organisation_id'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-17 years', '-7 years')->format('Y-m-d'),
            'shirt_number' => fake()->numberBetween(1, 30),
        ];
    }
}
