<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tags = [
            // Diet & lifestyle
            'vegetarian',
            'vegan',
            'gluten-free',
            'dairy-free',
            'low-carb',
            'keto',
            'paleo',
            'high-protein',

            // Meal types
            'breakfast',
            'lunch',
            'dinner',
            'snack',
            'dessert',

            // Cuisine
            'italian',
            'mexican',
            'asian',
            'indian',
            'mediterranean',
            'american',
            'french',

            // Difficulty & time
            'quick',
            'easy',
            'meal-prep',
            'one-pot',
            'weeknight',

            // Occasions
            'party',
            'holiday',
            'family',
            'comfort-food',

            // Health & misc
            'healthy',
            'spicy',
            'sweet',
            'savory',
            'kid-friendly',
        ];

        return [
            'name' => fake()->unique()->randomElement($tags),
        ];
    }
}
