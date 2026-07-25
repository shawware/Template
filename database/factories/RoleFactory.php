<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'display_name' => $this->faker->words(2, true),
            'is_superuser' => false,
        ];
    }

    public function superuser(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_superuser' => true,
        ]);
    }
}
