<?php

namespace App\Support\Image;

use App\Constants\StorageConstants;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class UserImageProcessor
{
    public static function process(TemporaryUploadedFile $file): string {
        return self::processPath($file->getRealPath());
    }

    public static function processSeedImage(string $path): string {
        return self::processPath($path);
    }

    private static function processPath(string $tmpPath): string {
        [$folder, $absoluteDir] = self::createImageDirectory();

        try {
            $image = self::loadImage($tmpPath);
            $image = self::limitMaxWidth($image, 1200);
            $image = self::cropSquare($image);

            self::saveWebp($image, "{$absoluteDir}/original.webp");
            self::resizeAndSaveWebp($image, 128, "{$absoluteDir}/128.webp");

            imagedestroy($image);

            return $folder;
        } catch (\Throwable $e) {
            Storage::deleteDirectory(StorageConstants::USER_IMAGES . '/' . $folder);
            throw $e;
        }
    }

    private static function createImageDirectory(): array
    {
        $base = StorageConstants::USER_IMAGES;

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

    private static function cropSquare(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // If already square, return as-is
        if ($width === $height) return $image;

        // Size of the square to be cropped to
        $size = min($width, $height);

        // Calculate x and y offset, so the cropped square is taken from the middle of the image | either $x or $y will be 0
        $x = (int) floor(($width - $size) / 2);
        $y = (int) floor(($height - $size) / 2);

        $cropped = imagecrop($image, [
            'x'      => $x,
            'y'      => $y,
            'width'  => $size,
            'height' => $size,
        ]);

        if ($cropped === false) throw new RuntimeException('Failed to crop image');

        imagedestroy($image);

        return $cropped;
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
        // Prevent upscaling
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
