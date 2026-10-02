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
            'name' => $ageGroup.' '.fake()->unique()->randomElement(['Lions', 'Tigers', 'Hawks', 'Falcons', 'Wolves', 'Eagles', 'Foxes', 'Badgers', 'Otters', 'Kestrels']),
            'age_group' => $ageGroup,
        ];
    }
}
