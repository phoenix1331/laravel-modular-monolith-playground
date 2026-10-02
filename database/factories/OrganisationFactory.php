<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organisation>
 */
class OrganisationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' '.fake()->randomElement(['FC', 'Rovers', 'United', 'Athletic', 'Juniors']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
