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
    public static function process(TemporaryUploadedFile $file, RecipeImageType $type): array {
        return self::processPath($file->getRealPath(), $type);
    }

    // Process an existing file path (for seeding)
    public static function processSeedImage(string $path, RecipeImageType $type): array {
        return self::processPath($path, $type);
    }

    private static function processPath(string $tmpPath, RecipeImageType $type): array {
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

        // Limit max width to 2000px immediately (also for server memory reasons)
        if (imagesx($image) > 2000) {
            $scaled = imagescale($image, 2000);
            imagedestroy($image);
            $image = $scaled;
        }

        // Capture final dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Always create original (max. 2000x), 1200px and 300px versions
        self::saveWebp($image, "{$absoluteDir}/original.webp");
        self::resizeAndSave($image, 1200, "{$absoluteDir}/1200.webp");
        self::resizeAndSave($image, 300, "{$absoluteDir}/300.webp");

        if ($type === RecipeImageType::PHOTO) {
            self::resizeAndSave($image, 600, "{$absoluteDir}/600.webp");
        }

        imagedestroy($image);

        return [
            'folder' => $folder,
            'width' => $width,
            'height' => $height,
        ];
    }

    private static function loadImage(string $path)
    {
        $type = exif_imagetype($path);

        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => self::prepareAlpha(imagecreatefrompng($path)),
            IMAGETYPE_GIF  => self::prepareAlpha(imagecreatefromgif($path)),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null,
        };

        if (!$image) return null;

        // Auto-rotate JPEG based on EXIF orientation
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);

            if (!empty($exif['Orientation'])) {
                $angle = match ($exif['Orientation']) {
                    3 => 180,
                    6 => -90,
                    8 => 90,
                    default => 0,
                };

                if ($angle !== 0) {
                    $rotated = imagerotate($image, $angle, 0);
                    imagedestroy($image); // free original immediately
                    $image = $rotated;
                }
            }
        }

        return $image;
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
