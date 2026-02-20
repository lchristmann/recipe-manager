<?php

namespace Database\Factories;

use App\Models\Cookbook;
use App\Models\User;
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
        $adjectives = [
            'Classic',
            'Creamy',
            'Spicy',
            'Sweet',
            'Savory',
            'Crispy',
            'Hearty',
            'Fresh',
            'Quick',
            'Easy',
            'Healthy',
            'Comfort',
        ];

        $dishes = [
            'Chicken Curry',
            'Beef Stew',
            'Vegetable Stir Fry',
            'Mushroom Pasta',
            'Tomato Soup',
            'Grilled Salmon',
            'Roasted Potatoes',
            'Fried Rice',
            'Caesar Salad',
            'Veggie Bowl',
            'Pancakes',
            'Apple Pie',
            'Chocolate Cake',
            'Banana Bread',
            'Fish Tacos',
            'Lasagna',
            'Risotto',
            'Burrito',
            'Omelette',
            'Quiche',
        ];

        $extras = [
            'with Herbs',
            'with Garlic',
            'with Lemon',
            'with Cheese',
            'with Vegetables',
            'with Spices',
            'with Rice',
            'with Pasta',
            'Style',
            'Delight',
        ];

        $title = collect([
            fake()->randomElement($adjectives),
            fake()->randomElement($dishes),
            fake()->boolean() ? fake()->randomElement($extras) : null,
        ])
            ->filter()
            ->join(' ');

        return [
            'cookbook_id' => Cookbook::factory(),
            'user_id' => User::factory(),
            'title' => $title,
            'description' => fake()->sentences(2, true),
            'ingredients' => fake()->paragraph(),
            'instructions' => fake()->paragraphs(3, true),
            'position' => fake()->numberBetween(1, 20),
        ];
    }
}
