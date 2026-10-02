<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'role' => Role::Worker,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function worker(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Worker]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Admin]);
    }

    /**
     * Super admins operate the platform and belong to no organisation.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::SuperAdmin,
            'organisation_id' => null,
        ]);
    }

    public function forOrganisation(Organisation $organisation): static
    {
        return $this->state(fn (array $attributes) => ['organisation_id' => $organisation->id]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
