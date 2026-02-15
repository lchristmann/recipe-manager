<?php

namespace App\Http\Controllers;

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use App\Models\RecipeImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RecipeImageController extends Controller
{
    /**
     * Serve a recipe/photo image using Nginx X-Accel-Redirect.
     */
    public function __invoke(RecipeImage $recipeImage, Request $request): Response
    {
        abort_unless(auth()->user()->can('view', $recipeImage->recipe), 403);

        $size = $request->query('size', 'original');
        if ($recipeImage->type === RecipeImageType::PHOTO) {
            $file = match($size) {
                '300', '600', '1200' => $size . '.webp',
                default => 'original.webp',
            };
            $locationPrefix = StorageConstants::PHOTO_IMAGES;
        } else { // recipe image
            $file = match ($size) {
                '300' => '300.webp',
                default => 'original.webp',
            };
            $locationPrefix = StorageConstants::RECIPE_IMAGES;
        }

        $path = "{$locationPrefix}/{$recipeImage->path}/{$file}"; // e.g. photo-images/01KHF8A1RNM2J7JWBDS2ES8B9E/300.webp

        if (!Storage::exists($path)) abort(404);

        return response('')
            ->header('X-Accel-Redirect', '/protected/' . $path)
            ->header('Content-Type', 'image/webp')
            ->header('Content-Disposition', 'inline; filename="' . $file . '"');
    }
}
