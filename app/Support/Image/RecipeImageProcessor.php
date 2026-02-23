<?php

namespace App\Support\Image;

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use GdImage;
use RuntimeException;

class RecipeImageProcessor
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
        [$folder, $absoluteDir] = self::createImageDirectory($type);

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
            Storage::deleteDirectory(($type === RecipeImageType::PHOTO ? StorageConstants::PHOTO_IMAGES : StorageConstants::RECIPE_IMAGES) . '/' . $folder);
            throw $e;
        }
    }

    private static function createImageDirectory(RecipeImageType $type): array
    {
        $base = $type === RecipeImageType::PHOTO ? StorageConstants::PHOTO_IMAGES : StorageConstants::RECIPE_IMAGES;

        do {
            $folder = (string) Str::ulid();
        } while (Storage::exists("{$base}/{$folder}"));

        $directory = "{$base}/{$folder}";
        Storage::makeDirectory($directory);

        $absoluteDir = storage_path("app/private/{$directory}");
        chmod($absoluteDir, 0775);

        return [$folder, $absoluteDir];
    }

    private static function loadImage(string $path): GdImage
    {
        $type = exif_imagetype($path);

        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => self::prepareAlpha(imagecreatefrompng($path)),
            IMAGETYPE_GIF  => self::prepareAlpha(imagecreatefromgif($path)),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null,
        };

        if (!$image) throw new RuntimeException('Unsupported image format');

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
                    if (!$rotated) return $image; // If rotation failed (unlikely, but possible), keep original image
                    imagedestroy($image); // free original immediately
                    $image = $rotated;
                }
            }
        }

        return $image;
    }

    private static function limitMaxWidth(GdImage $image, int $maxWidth): GdImage
    {
        if (imagesx($image) <= $maxWidth) return $image;

        $scaled = imagescale($image, $maxWidth);
        if (!$scaled) throw new RuntimeException('Failed to scale image');

        imagedestroy($image);
        return $scaled;
    }

    private static function prepareAlpha(GdImage $image): GdImage
    {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        return $image;
    }

    private static function resizeAndSaveWebp(GdImage $image, int $width, string $target): void
    {
        if (imagesx($image) <= $width) {
            if (!imagewebp($image, $target, 90)) {
                throw new RuntimeException("Failed to write image to {$target}");
            }
            return;
        }

        $scaled = imagescale($image, $width);
        if (!$scaled) throw new RuntimeException('Failed to scale image');

        if (!imagewebp($scaled, $target, 90)) {
            imagedestroy($scaled);
            throw new RuntimeException("Failed to write image to {$target}");
        }

        imagedestroy($scaled);
    }

    private static function saveWebp(GdImage $image, string $target): void
    {
        if (!imagewebp($image, $target, 95)) {
            throw new RuntimeException("Failed to write image to {$target}");
        }
    }
}
