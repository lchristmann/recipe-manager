<?php

namespace App\Support\Image;

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RecipeImageProcessor extends BaseImageProcessor
{
    // Process a real uploaded Livewire TemporaryUploadedFile (used in production)
    public static function process(TemporaryUploadedFile $file, RecipeImageType $type): array {
        return self::processPath($file->getRealPath(), $type);
    }

    // Process an existing file path (for seeding)
    public static function processSeedImage(string $path, RecipeImageType $type): array {
        return self::processPath($path, $type);
    }

    private static function processPath(string $tmpPath, RecipeImageType $type): array {
        $base = $type === RecipeImageType::PHOTO ? StorageConstants::PHOTO_IMAGES : StorageConstants::RECIPE_IMAGES;

        [$folder, $absoluteDir] = self::createImageDirectory($base);

        try {
            $image = self::loadImage($tmpPath);
            $image = self::limitMaxWidth($image, 2000);

            // Capture final dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Always create original (max. 2000x), 1200px and 300px versions
            self::saveWebp($image, "{$absoluteDir}/original.webp");
            self::resizeAndSaveWebp($image, 1200, "{$absoluteDir}/1200.webp");
            self::resizeAndSaveWebp($image, 300, "{$absoluteDir}/300.webp");

            if ($type === RecipeImageType::PHOTO) {
                self::resizeAndSaveWebp($image, 600, "{$absoluteDir}/600.webp");
            }

            imagedestroy($image);

            return [
                'folder' => $folder,
                'width' => $width,
                'height' => $height,
            ];
        } catch (\Throwable $e) {
            Storage::deleteDirectory($base . '/' . $folder);
            throw $e;
        }
    }
}
