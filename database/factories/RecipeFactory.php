<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
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
        $visibility = fake()->boolean(75) ? 'public' : 'private';
        $imagePath = null;

        if (fake()->boolean()) {
            // Generate a dummy image file locally
            $imageContent = file_get_contents('https://placehold.co/600x400.png?text=Recipe+Image');
            $tempFilePath = sys_get_temp_dir() . '/' . fake()->uuid() . '.png';
            file_put_contents($tempFilePath, $imageContent);

            // Store on the disk depending on visibility
            $diskName = $visibility === 'private' ? 'local' : 'public';
            $disk = Storage::disk($diskName);
            $imagePath = $disk->putFile('images', new File($tempFilePath));

            // Clean up temp file
            unlink($tempFilePath);
        }

        return [
            'image_path' => $imagePath,
            'title' => fake()->words(2, true),
            'content' => fake()->paragraphs(3, true),
            'visibility' => $visibility,
            'folder_id' => Folder::factory(),
        ];
    }
}
