<?php

declare(strict_types=1);

namespace Modules\Fixtures\Database\Factories;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Fixtures\Models\Venue;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'name' => fake()->unique()->lastName().' '.fake()->randomElement(['Park', 'Recreation Ground', 'Playing Fields', 'Lane']),
            'address' => fake()->streetAddress(),
            'postcode' => fake()->postcode(),
        ];
    }
}
