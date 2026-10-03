<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organisation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ageGroup = fake()->randomElement(['U9', 'U10', 'U11', 'U12', 'U13', 'U14', 'U16', 'Seniors']);

        return [
            'organisation_id' => Organisation::factory(),
            // Surname-based so generated names never collide with the readable names tests set explicitly (e.g. "U12 Lions")
            'name' => fake()->unique()->lastName().' '.$ageGroup,
            'age_group' => $ageGroup,
        ];
    }
}
