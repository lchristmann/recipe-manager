<?php

namespace App\Support\Image;

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class RecipeImageProcessor
{
    // process a real uploaded Livewire TemporaryUploadedFile (used in production)
    public static function process(TemporaryUploadedFile $file, RecipeImageType $type): string {
        return self::processPath($file->getRealPath(), $type);
    }

    // Process an existing file path (for seeding)
    public static function processSeedImage(string $path, RecipeImageType $type): string {
        return self::processPath($path, $type);
    }

    private static function processPath(string $tmpPath, RecipeImageType $type): string {
        $base = $type === RecipeImageType::PHOTO ? StorageConstants::PHOTO_IMAGES : StorageConstants::RECIPE_IMAGES;

        do {
            $folder = (string) Str::ulid();
        } while (Storage::exists("{$base}/{$folder}"));

        $directory = "{$base}/{$folder}";
        Storage::makeDirectory($directory);
        $absoluteDir = storage_path("app/private/{$directory}");
        chmod($absoluteDir, 0775);

        $image = self::loadImage($tmpPath);

        if (!$image) throw new RuntimeException('Unsupported image format');

        // Always create original.webp
        self::saveWebp($image, "{$absoluteDir}/original.webp");

        // Always create 300px
        self::resizeAndSave($image, 300, "{$absoluteDir}/300.webp");

        if ($type === RecipeImageType::PHOTO) {
            self::resizeAndSave($image, 600, "{$absoluteDir}/600.webp");
            self::resizeAndSave($image, 1200, "{$absoluteDir}/1200.webp");
        }

        imagedestroy($image);

        return $folder;
    }

    private static function loadImage(string $path)
    {
        return match (exif_imagetype($path)) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => self::prepareAlpha(imagecreatefrompng($path)),
            IMAGETYPE_GIF  => self::prepareAlpha(imagecreatefromgif($path)),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null,
        };
    }

    private static function prepareAlpha($image)
    {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        return $image;
    }

    private static function resizeAndSave($image, int $width, string $target): void
    {
        // Prevent upscaling
        if (imagesx($image) <= $width) {
            imagewebp($image, $target, 90);
            return;
        }

        $scaled = imagescale($image, $width);
        imagewebp($scaled, $target, 90);
        imagedestroy($scaled);
    }

    private static function saveWebp($image, string $target): void
    {
        imagewebp($image, $target, 95);
    }
}
