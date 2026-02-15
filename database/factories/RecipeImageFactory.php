<?php

namespace Database\Factories;

use App\Enums\RecipeImageType;
use App\Models\Recipe;
use App\Models\RecipeImage;
use App\Support\Image\RecipeImageProcessor;
use Illuminate\Database\Eloquent\Factories\Factory;

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

            $sampleFiles = $isPhoto
                ? ['sample-photo-image-1.jpg', 'sample-photo-image-2.jpg']
                : ['sample-recipe-image-1.png', 'sample-recipe-image-2.jpg'];

            $selectedFile = $sampleFiles[array_rand($sampleFiles)];
            $sourcePath = database_path('seeders/files/' . $selectedFile);

            // Process the image and generate the webp sizes
            $folder = RecipeImageProcessor::processSeedImage($sourcePath, $image->type);

            $image->updateQuietly([
                'path' => $folder,
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
