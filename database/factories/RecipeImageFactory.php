<?php

namespace Database\Factories;

use App\Constants\StorageConstants;
use App\Enums\Enums\RecipeImageType;
use App\Models\Recipe;
use App\Models\RecipeImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeImage>
 */
class RecipeImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'path' => '',
            'type' => fake()->randomElement([
                RecipeImageType::PHOTO,
                RecipeImageType::RECIPE,
            ]),
            'position' => fake()->numberBetween(1, 5),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (RecipeImage $image) {
            $isPhoto = $image->type === RecipeImageType::PHOTO;

            $sourcePath = database_path(
                'seeders/files/sample-' .
                ($isPhoto ? 'photo' : 'recipe') .
                '-image-' .
                (fake()->boolean ? '1' : '2') .
                '.jpg'
            );

            $destinationPath = StorageConstants::RECIPE_IMAGES . '/' . uniqid() . '.jpg';

            Storage::put($destinationPath, file_get_contents($sourcePath));

            $image->updateQuietly([
                'path' => $destinationPath,
            ]);
        });
    }

    public function photo(): static
    {
        return $this->state(fn () => ['type' => RecipeImageType::PHOTO]);
    }

    public function recipe(): static
    {
        return $this->state(fn () => ['type' => RecipeImageType::RECIPE]);
    }
}

/*
 * Info about photo and recipe images put under seeders/files:
 *
 * Photo Images:
 * - https://unsplash.com/photos/text-YbTgPbMTgWk (medium)
 * - https://unsplash.com/photos/white-and-red-labeled-book-rN_RMqSXRKw (medium)
 *
 * Recipe Images:
 * - https://unsplash.com/photos/cooked-noodles-with-shrimps-r01ZopTiEV8 (small)
 * - https://unsplash.com/photos/cooked-tacos-lP5MCM6nZ5A (small)
 */
