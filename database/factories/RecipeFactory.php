<?php

namespace Database\Factories;

use App\Models\RecipeBook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_book_id' => RecipeBook::factory(),
            'title' => fake()->words(3, true),
            'link' => fake()->boolean(30) ? fake()->url() : null,
            'ingredients' => fake()->paragraph(),
            'instructions' => fake()->paragraphs(3, true),
            'position' => fake()->numberBetween(1, 20),
        ];
    }
}
