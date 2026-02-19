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
        $base = StorageConstants::USER_IMAGES;

        do {
            $folder = (string) Str::ulid();
        } while (Storage::exists("{$base}/{$folder}"));

        $directory = "{$base}/{$folder}";
        Storage::makeDirectory($directory);
        $absoluteDir = storage_path("app/private/{$directory}");
        chmod($absoluteDir, 0775);

        $image = self::loadImage($tmpPath);

        if (!$image) throw new RuntimeException('Unsupported image format');

        // Limit max width to 1200px immediately (also for server memory reasons)
        if (imagesx($image) > 1200) {
            $scaled = imagescale($image, 1200);
            imagedestroy($image);
            $image = $scaled;
        }

        $image = self::cropSquare($image);

        // Create original.webp (max 1200x1200)
        self::saveWebp($image, "{$absoluteDir}/original.webp");

        // Create 128.webp
        self::resizeAndSave($image, 128, "{$absoluteDir}/128.webp");

        imagedestroy($image);

        return $folder;
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

    private static function cropSquare($image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $size = min($width, $height);

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
