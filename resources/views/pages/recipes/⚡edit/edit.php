<?php

use App\Constants\StorageConstants;
use App\Enums\Enums\RecipeImageType;
use App\Models\Recipe;
use App\Models\RecipeImage;
use App\Models\Tag;
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

    public Recipe $recipe;

    // Form fields
    public string $title;
    public ?string $description;
    public ?string $ingredients;
    public ?string $instructions;

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

        $this->title = $recipe->title;
        $this->description = $recipe->description;
        $this->ingredients = $recipe->ingredients;
        $this->instructions = $recipe->instructions;

        $this->selectedTags = $recipe->tags()->pluck('tags.id')->toArray();

        $this->links = $recipe->links()->pluck('url')->toArray();

        $this->photoImages = $recipe->photoImages()->get()
            ->map(function (RecipeImage $img, int $index) {
                $extension = pathinfo($img->path, PATHINFO_EXTENSION);
                $heading = "image-" . ($index + 1) . "." . $extension;

                return [
                    'id' => $img->id,
                    'file' => null,
                    'preview' => route('recipe-images.show', $img),
                    'key' => 'photo-' . $img->id,
                    'heading' => $heading,
                    'size' => Storage::size($img->path),
                ];
            })->toArray();

        $this->recipeImages = $recipe->recipeImages()->get()
            ->map(function (RecipeImage $img, int $index) {
                $extension = pathinfo($img->path, PATHINFO_EXTENSION);
                $heading = "image-" . ($index + 1) . "." . $extension;

                return [
                    'id' => $img->id,
                    'file' => null,
                    'preview' => route('recipe-images.show', $img),
                    'key' => 'recipe-' . $img->id,
                    'heading' => $heading,
                    'size' => Storage::size($img->path),
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
                    Storage::delete($image->path);
                    $image->delete();
                });

            RecipeImage::whereIn('id', $this->deletedRecipeImageIds)
                ->each(function (RecipeImage $image) {
                    Storage::delete($image->path);
                    $image->delete();
                });

            foreach ($this->photoImages as $position => $image) {
                if ($image['id']) {
                    $this->recipe->images()->whereKey($image['id'])->update(['position' => $position]);
                } elseif ($image['file']) {
                    $path = $image['file']->store(path: StorageConstants::PHOTO_IMAGES);
                    $this->recipe->images()->create([
                        'path' => $path,
                        'type' => RecipeImageType::PHOTO,
                        'position' => $position,
                    ]);
                }
            }

            foreach ($this->recipeImages as $position => $image) {
                if ($image['id']) {
                    $this->recipe->images()->whereKey($image['id'])->update(['position' => $position]);
                } elseif ($image['file']) {
                    $path = $image['file']->store(path: StorageConstants::RECIPE_IMAGES);
                    $this->recipe->images()->create([
                        'path' => $path,
                        'type' => RecipeImageType::RECIPE,
                        'position' => $position,
                    ]);
                }
            }
        });

        $this->redirectRoute('recipes.show', $this->recipe);
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
