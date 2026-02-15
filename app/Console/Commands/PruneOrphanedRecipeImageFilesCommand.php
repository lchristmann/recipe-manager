<?php

namespace App\Console\Commands;

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use App\Models\RecipeImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * This command is useful, because when users or cookbooks are deleted, it cascades all the way down to RecipeImage records,
 * but the folders containing the actual recipe image files would persist, if this command wouldn't prune them
 */
class PruneOrphanedRecipeImageFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:prune-orphaned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete orphaned recipe/photo image folders that no longer belong to any RecipeImage record';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->prune(RecipeImageType::PHOTO);
        $this->prune(RecipeImageType::RECIPE);
    }

    private function prune(RecipeImageType $type): void
    {
        $baseFolder = match ($type) {
            RecipeImageType::PHOTO => StorageConstants::PHOTO_IMAGES,
            RecipeImageType::RECIPE => StorageConstants::RECIPE_IMAGES,
        };

        $folders = Storage::directories($baseFolder);

        // All folders referenced by RecipeImage records
        $pathsInDatabase = RecipeImage::query()
            ->where('type', $type)
            ->pluck('path')
            ->all();

        // Orphans = on disk but not in DB
        $orphans = array_diff($folders, array_map(fn($p) => "{$baseFolder}/{$p}", $pathsInDatabase));

        $deletedCount = 0;
        foreach ($orphans as $folder) {
            Storage::deleteDirectory($folder);
            $deletedCount++;
        }

        $this->info("Deleted {$deletedCount} orphaned {$baseFolder} folder(s).");
    }
}
