<?php

namespace Database\Factories;

use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeLink>
 */
class RecipeLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sites = [
            'www.chefkoch.de',
            'emmikochteinfach.de',
            'www.einfachbacken.de',
            'www.einfachkochen.de',
            'www.allrecipes.com',
            'www.bbcgoodfood.com',
            'www.seriouseats.com',
        ];

        $domain = fake()->randomElement($sites);

        return [
            'recipe_id' => Recipe::factory(),
            'url' => 'https://'.$domain.'/'.fake()->slug(),
            'position' => fake()->numberBetween(1, 5),
        ];
    }
}
