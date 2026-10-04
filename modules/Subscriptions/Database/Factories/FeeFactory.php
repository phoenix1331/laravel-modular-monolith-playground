<?php

declare(strict_types=1);

namespace Modules\Subscriptions\Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Subscriptions\Models\Fee;

/**
 * @extends Factory<Fee>
 */
class FeeFactory extends Factory
{
    protected $model = Fee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            // Always the player's organisation; the scope is bypassed because factories often run with no current organisation
            'organisation_id' => fn (array $attributes) => Player::withoutOrganisationScope()->whereKey($attributes['player_id'])->value('organisation_id'),
            'description' => '2026/27 season subs',
            'amount_pence' => 12000,
            'due_on' => now()->addMonth()->toDateString(),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['due_on' => now()->subWeek()->toDateString()]);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['paid_at' => now(), 'payment_reference' => 'fake_seeded']);
    }
}
