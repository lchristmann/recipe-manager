<?php

namespace App\Support\Image;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

abstract class BaseImageProcessor
{
    protected static function createImageDirectory(string $base): array
    {
        do {
            $folder = (string) Str::ulid();
        } while (Storage::exists("{$base}/{$folder}"));

        $directory = "{$base}/{$folder}";
        Storage::makeDirectory($directory);

        $absoluteDir = storage_path("app/private/{$directory}");
        chmod($absoluteDir, 0775);

        return [$folder, $absoluteDir];
    }

    protected static function loadImage(string $path): GdImage
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

    protected static function limitMaxWidth(GdImage $image, int $maxWidth): GdImage
    {
        if (imagesx($image) <= $maxWidth) return $image;

        $scaled = imagescale($image, $maxWidth);
        if (!$scaled) throw new RuntimeException('Failed to scale image');

        imagedestroy($image);
        return $scaled;
    }

    protected static function prepareAlpha(GdImage $image): GdImage
    {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        return $image;
    }

    protected static function resizeAndSaveWebp(GdImage $image, int $width, string $target): void
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

    protected static function saveWebp(GdImage $image, string $target): void
    {
        if (!imagewebp($image, $target, 95)) {
            throw new RuntimeException("Failed to write image to {$target}");
        }
    }
}
