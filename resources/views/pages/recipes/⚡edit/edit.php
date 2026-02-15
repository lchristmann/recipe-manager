<?php

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use App\Models\Cookbook;
use App\Models\Recipe;
use App\Models\RecipeImage;
use App\Models\Tag;
use App\Support\Image\RecipeImageProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    // Modal state
    public bool $showDeleteModal = false;

    public Recipe $recipe;

    public bool $cookbookUnlocked = false;
    public int $selectedCookbookId;

    // Form fields
    public string $title;
    public ?string $description = null;
    public ?string $ingredients = null;
    public ?string $instructions = null;

    // Tags
    public array $selectedTags = [];
    public array $createdTagIds = [];
    public string $tagSearch = '';

    // Links
    public array $links = [];

    // Images (existing + new)
    public array $photoImages = [];
    public array $recipeImages = [];
    public array $deletedPhotoImageIds = []; // for existing images
    public array $deletedRecipeImageIds = [];

    // Temporary uploads
    public array $newPhotoFiles = [];
    public array $newRecipeFiles = [];

    protected function rules(): array
    {
        return [
            'selectedCookbookId' => ['required', Rule::exists('cookbooks', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ingredients' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'selectedTags' => ['array', Rule::exists('tags', 'id')],
            'links' => ['array'],
            'links.*' => ['nullable', 'url:http,https', 'max:255'],
            'photoImages' => ['array'],
            'photoImages.*.file' => ['nullable', 'image', 'max:10240'],
            'recipeImages' => ['array'],
            'recipeImages.*.file' => ['nullable', 'image', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'links.*.url' => __('Link #:position must be a valid URL.'),
            'links.*.max' => __('Link #:position may not exceed :max characters.'),
            'photoImages.*.file.image' => __('Photo #:position must be an image file.'),
            'photoImages.*.file.max' => __('Photo #:position may not be larger than 10 MB.'),
            'recipeImages.*.file.image' => __('Recipe image #:position must be an image file.'),
            'recipeImages.*.file.max' => __('Recipe image #:position may not be larger than 8 MB.'),
        ];
    }

    // -------------------- lifecycle hooks --------------------

    public function mount(Recipe $recipe): void
    {
        $this->authorize('update', $recipe);
        $this->recipe = $recipe;

        $this->selectedCookbookId = $recipe->cookbook_id;

        $this->title = $recipe->title;
        $this->description = $recipe->description;
        $this->ingredients = $recipe->ingredients;
        $this->instructions = $recipe->instructions;

        $this->selectedTags = $recipe->tags()->pluck('tags.id')->toArray();

        $this->links = $recipe->links()->pluck('url')->toArray();

        $this->photoImages = $recipe->photoImages()->get()
            ->map(function (RecipeImage $img, int $index) {
                return [
                    'id' => $img->id,
                    'file' => null,
                    'preview' => route('recipe-images.show', [$img, 'size' => 300]),
                    'key' => 'photo-' . $img->id,
                    'heading' => 'image-' . ($index + 1) . '.webp',
                    'size' => Storage::size(StorageConstants::PHOTO_IMAGES . '/' . $img->path . '/300.webp'),
                ];
            })->toArray();

        $this->recipeImages = $recipe->recipeImages()->get()
            ->map(function (RecipeImage $img, int $index) {
                return [
                    'id' => $img->id,
                    'file' => null,
                    'preview' => route('recipe-images.show', [$img, 'size' => 300]),
                    'key' => 'recipe-' . $img->id,
                    'heading' => 'image-' . ($index + 1) . '.webp',
                    'size' => Storage::size(StorageConstants::RECIPE_IMAGES . '/' . $img->path . '/300.webp'),
                ];
            })->toArray();
    }

    public function updatedNewPhotoFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $this->photoImages[] = [
                    'id' => null,
                    'file' => $file,
                    'preview' => $file->isPreviewable() ? $file->temporaryUrl() : null,
                    'key' => 'new-' . uniqid(),
                    'heading' => $file->getClientOriginalName(),
                    'size'    => $file->getSize(),
                ];
            }
        }

        // Clear temporary files to avoid duplication
        $this->newPhotoFiles = [];
    }

    public function updatedNewRecipeFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $this->recipeImages[] = [
                    'id' => null,
                    'file' => $file,
                    'preview' => $file->isPreviewable() ? $file->temporaryUrl() : null,
                    'key' => 'new-' . uniqid(),
                    'heading' => $file->getClientOriginalName(),
                    'size'    => $file->getSize(),
                ];
            }
        }

        $this->newRecipeFiles = [];
    }

    // -------------------- queries --------------------

    #[Computed]
    public function communityCookbooks(): Collection
    {
        return Cookbook::query()->community()->get();
    }

    #[Computed]
    public function userCookbooks(): Collection
    {
        return auth()->user()?->personalCookbooks()->get() ?? collect();
    }

    #[Computed]
    public function selectedCookbook(): Cookbook
    {
        return Cookbook::findOrFail($this->selectedCookbookId);
    }

    #[Computed]
    public function allTags(): Collection
    {
        return Tag::query()
            ->where(function (Builder $q) {
                $q->whereHas('recipes', function (Builder $query) {
                    $query->whereHas('cookbook', function (Builder $q) {
                        $q->where('community', true)
                            ->orWhere('user_id', auth()->id());
                    });
                })
                    ->orWhereIn('id', $this->createdTagIds);
            })
            ->orderBy('name')
            ->get();
    }

    // -------------------- helper methods --------------------

    public function addLink(): void
    {
        $this->links[] = '';
    }

    public function removeLink(int $index): void
    {
        unset($this->links[$index]);
        $this->links = array_values($this->links);
    }

    public function removePhotoImage(int $index): void
    {
        $image = $this->photoImages[$index] ?? null;
        if (!$image) return;

        // Existing image -> mark for deletion
        if (!empty($image['id'])) {
            $this->deletedPhotoImageIds[] = $image['id'];
        }

        // Temporary upload -> delete temp file
        if (isset($image['file']) && $image['file'] instanceof TemporaryUploadedFile) {
            $image['file']->delete();
        }

        unset($this->photoImages[$index]);
        $this->photoImages = array_values($this->photoImages);
    }

    public function removeRecipeImage(int $index): void
    {
        $image = $this->recipeImages[$index] ?? null;
        if (!$image) return;

        if (!empty($image['id'])) {
            $this->deletedRecipeImageIds[] = $image['id'];
        }

        if (isset($image['file']) && $image['file'] instanceof TemporaryUploadedFile) {
            $image['file']->delete();
        }

        unset($this->recipeImages[$index]);
        $this->recipeImages = array_values($this->recipeImages);
    }

    // -------------------- update --------------------

    public function createTag(): void
    {
        $name = trim($this->tagSearch);

        if (strlen($name) < 2) {
            return;
        }

        $tag = Tag::firstOrCreate(['name' => $name]);

        $this->selectedTags[] = $tag->id;
        $this->createdTagIds[] = $tag->id;

        $this->tagSearch = '';
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {

            // If the cookbook was unlocked and changed, update it
            if ($this->cookbookUnlocked && $this->selectedCookbookId !== $this->recipe->cookbook_id) {
                $oldCookbookId = $this->recipe->cookbook_id;
                $newCookbook = Cookbook::findOrFail($this->selectedCookbookId);
                $this->authorize('update', $newCookbook);

                // Close positions gap in old cookbook
                Recipe::query()
                    ->where('cookbook_id', $oldCookbookId)
                    ->where('position', '>', $this->recipe->position)
                    ->decrement('position');

                $newPosition = Recipe::query()->where('cookbook_id', $newCookbook->id)->count();

                $this->recipe->update([
                    'cookbook_id' => $newCookbook->id,
                    'position' => $newPosition,
                ]);
            }

            $this->recipe->update([
                'title' => $this->title,
                'description' => $this->description ?: null,
                'ingredients' => $this->ingredients ?: null,
                'instructions' => $this->instructions ?: null,
            ]);

            $this->recipe->tags()->sync($this->selectedTags);

            $this->recipe->links()->delete();
            foreach ($this->links as $position => $url) {
                if ($url) {
                    $this->recipe->links()->create([
                        'url' => $url,
                        'position' => $position,
                    ]);
                }
            }

            RecipeImage::whereIn('id', $this->deletedPhotoImageIds)
                ->each(function (RecipeImage $image) {
                    $base = $image->type === RecipeImageType::PHOTO ? StorageConstants::PHOTO_IMAGES : StorageConstants::RECIPE_IMAGES;
                    Storage::deleteDirectory("{$base}/{$image->path}");
                    $image->delete();
                });

            RecipeImage::whereIn('id', $this->deletedRecipeImageIds)
                ->each(function (RecipeImage $image) {
                    $base = $image->type === RecipeImageType::PHOTO ? StorageConstants::PHOTO_IMAGES : StorageConstants::RECIPE_IMAGES;
                    Storage::deleteDirectory("{$base}/{$image->path}");
                    $image->delete();
                });

            foreach ($this->photoImages as $position => $image) {
                if ($image['id']) {
                    $this->recipe->images()->whereKey($image['id'])->update(['position' => $position]);
                } elseif ($image['file']) {
                    $folder = RecipeImageProcessor::process($image['file'], RecipeImageType::PHOTO);
                    $this->recipe->images()->create([
                        'path' => $folder,
                        'type' => RecipeImageType::PHOTO,
                        'position' => $position,
                    ]);
                }
            }

            foreach ($this->recipeImages as $position => $image) {
                if ($image['id']) {
                    $this->recipe->images()->whereKey($image['id'])->update(['position' => $position]);
                } elseif ($image['file']) {
                    $folder = RecipeImageProcessor::process($image['file'], RecipeImageType::RECIPE);
                    $this->recipe->images()->create([
                        'path' => $folder,
                        'type' => RecipeImageType::RECIPE,
                        'position' => $position,
                    ]);
                }
            }
        });

        $this->redirectRoute('recipes.show', $this->recipe);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->recipe);

        $cookbookId = $this->recipe->cookbook_id;

        DB::transaction(function () use ($cookbookId) {
            $position = $this->recipe->position;

            // Collect image paths and types before deletion of the recipe model
            $images = $this->recipe->images()->get(['path', 'type']);

            // Close gap in the cookbook's recipe list
            Recipe::query()
                ->where('cookbook_id', $cookbookId)
                ->where('position', '>', $position)
                ->decrement('position');

            $this->recipe->delete();

            // Delete the recipe's image files from storage
            foreach ($images as $image) {
                $base = $image->type === RecipeImageType::PHOTO ? StorageConstants::PHOTO_IMAGES : StorageConstants::RECIPE_IMAGES;
                Storage::deleteDirectory("{$base}/{$image->path}");
            }
        });

        $this->showDeleteModal = false;

        $this->redirectRoute('cookbooks.show', $cookbookId);
    }

    // -------------------- modal helpers --------------------

    public function openDeleteModal(): void
    {
        $this->authorize('delete', $this->recipe);
        $this->showDeleteModal = true;
    }

    // -------------------- sorting handlers --------------------

    public function sortPhotoImages(int $index, int $newPosition): void
    {
        $item = $this->photoImages[$index] ?? null;
        if (!$item) return;

        array_splice($this->photoImages, $index, 1);
        array_splice($this->photoImages, $newPosition, 0, [$item]);
    }

    public function sortRecipeImages(int $index, int $newPosition): void
    {
        $item = $this->recipeImages[$index] ?? null;
        if (!$item) return;

        array_splice($this->recipeImages, $index, 1);
        array_splice($this->recipeImages, $newPosition, 0, [$item]);
    }
};
