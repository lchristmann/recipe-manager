<?php

namespace App\Http\Controllers;

use App\Constants\StorageConstants;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class UserImageController extends Controller
{
    /**
     * Serve a user image using Nginx X-Accel-Redirect.
     */
    public function __invoke(User $user, Request $request): Response
    {
        abort_unless($user->image_path, 404);

        $size = $request->query('size', 'original');
        $file = match ($size) {
            '128' => '128.webp',
            default => 'original.webp',
        };

        $path = StorageConstants::USER_IMAGES . '/' . $user->image_path . '/' . $file;

        abort_unless(Storage::exists($path), 404);

        return response('')
            ->header('X-Accel-Redirect', '/protected/' . $path)
            ->header('Content-Type', 'image/webp')
            ->header('Content-Disposition', 'inline; filename="' . $file . '"');
    }
}
