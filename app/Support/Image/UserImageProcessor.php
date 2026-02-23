<?php

namespace App\Support\Image;

use App\Constants\StorageConstants;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class UserImageProcessor extends BaseImageProcessor
{
    public static function process(TemporaryUploadedFile $file): string {
        return self::processPath($file->getRealPath());
    }

    public static function processSeedImage(string $path): string {
        return self::processPath($path);
    }

    private static function processPath(string $tmpPath): string {
        $base = StorageConstants::USER_IMAGES;

        [$folder, $absoluteDir] = self::createImageDirectory($base);

        try {
            $image = self::loadImage($tmpPath);
            $image = self::limitMaxWidth($image, 1200);
            $image = self::cropSquare($image);

            self::saveWebp($image, "{$absoluteDir}/original.webp");
            self::resizeAndSaveWebp($image, 128, "{$absoluteDir}/128.webp");

            imagedestroy($image);

            return $folder;
        } catch (\Throwable $e) {
            Storage::deleteDirectory($base . '/' . $folder);
            throw $e;
        }
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
}
