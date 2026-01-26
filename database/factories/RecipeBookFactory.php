<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeBook>
 */
class RecipeBookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'subtitle' => fake()->sentence(),
            'community' => false,
            'private' => false,
            'position' => fake()->numberBetween(1, 10),
        ];
    }

    public function community(): static
    {
        return $this->state(fn () => ['community' => true]);
    }

    public function private(): static
    {
        return $this->state(fn () => ['private' => true]);
    }
}
