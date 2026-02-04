<?php

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use App\Models\Cookbook;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Url(as: 'cookbook')]
    public ?int $cookbookId = null;

    // Form fields
    public ?int $selectedCookbookId = null;
    public string $title = '';
    public ?string $description = null;
    public ?string $ingredients = null;
    public ?string $instructions = null;

    // Tags pillbox
    public array $selectedTags = [];
    public array $createdTagIds = [];
    public string $tagSearch = '';

    public array $links = [''];

    // Images
    public array $photoImages = [];
    public array $recipeImages = [];

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
            'photoImages.*' => ['image', 'max:10240'],
            'recipeImages' => ['array'],
            'recipeImages.*' => ['image', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'links.*.url' => __('Link #:position must be a valid URL.'),
            'links.*.max' => __('Link #:position may not exceed :max characters.'),
            'photoImages.*.image' => __('Photo #:position must be an image file.'),
            'photoImages.*.max' => __('Photo #:position may not be larger than 10 MB.'),
            'recipeImages.*.image' => __('Recipe image #:position must be an image file.'),
            'recipeImages.*.max' => __('Recipe image #:position may not be larger than 8 MB.'),
        ];
    }

    public function mount(): void
    {
        if ($this->cookbookId) {
            $cookbook = Cookbook::findOrFail($this->cookbookId);
            $this->authorize('update', $cookbook);

            $this->selectedCookbookId = $cookbook->id;
        }
    }

    // -------------------- queries --------------------

    #[Computed]
    public function selectedCookbook(): ?Cookbook
    {
        if (!$this->selectedCookbookId) {
            return null;
        }

        return Cookbook::find($this->selectedCookbookId);
    }

    #[Computed]
    public function userCookbooks(): Collection
    {
        return auth()->user()?->personalCookbooks()->get() ?? collect();
    }

    #[Computed]
    public function communityCookbooks(): Collection
    {
        return Cookbook::query()->community()->get();
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
        $this->links[] = ''; // append empty input
    }

    public function removeLink(int $index): void
    {
        unset($this->links[$index]);
        $this->links = array_values($this->links); // reindex the array
    }

    public function removePhotoImage(int $index): void
    {
        $file = $this->photoImages[$index] ?? null;

        if ($file) $file->delete();

        unset($this->photoImages[$index]);
        $this->photoImages = array_values($this->photoImages);
    }

    public function removeRecipeImage(int $index): void
    {
        $file = $this->recipeImages[$index] ?? null;

        if ($file) $file->delete();

        unset($this->recipeImages[$index]);
        $this->recipeImages = array_values($this->recipeImages);
    }


    // -------------------- create / update / delete --------------------

    public function createTag(): void
    {
        $name = trim($this->tagSearch);

        if (strlen($name) < 2) {
            return;
        }

        $tag = Tag::firstOrCreate(['name' => $name]);

        $this->createdTagIds[] = $tag->id;
        $this->selectedTags[] = $tag->id;

        $this->tagSearch = '';
    }

    public function save(): void
    {
        $validated = $this->validate();

        $cookbook = Cookbook::findOrFail($validated['selectedCookbookId']);

        $this->authorize('update', $cookbook);

        $recipe = DB::transaction(function () use ($validated, $cookbook) {
            $position = Recipe::query()
                ->where('cookbook_id', $cookbook->id)
                ->count();

            $recipe = Recipe::create([
                'cookbook_id' => $cookbook->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?: null,
                'ingredients' => $validated['ingredients'] ?: null,
                'instructions' => $validated['instructions'] ?: null,
                'position' => $position,
            ]);

            if (!empty($validated['selectedTags'])) {
                $recipe->tags()->sync($validated['selectedTags']);
            }

            foreach ($this->links as $position => $url) {
                if (!empty($url)) {
                    $recipe->links()->create([
                        'url' => $url,
                        'position' => $position,
                    ]);
                }
            }

            // Store photo images
            foreach ($this->photoImages as $position => $file) {
                if ($file) {
                    $path = $file->store(path: StorageConstants::PHOTO_IMAGES);
                    $recipe->images()->create([
                        'path' => $path,
                        'type' => RecipeImageType::PHOTO,
                        'position' => $position,
                    ]);
                }
            }

            // Store recipe images
            foreach ($this->recipeImages as $position => $file) {
                if ($file) {
                    $path = $file->store(path: StorageConstants::RECIPE_IMAGES);
                    $recipe->images()->create([
                        'path' => $path,
                        'type' => RecipeImageType::RECIPE,
                        'position' => $position,
                    ]);
                }
            }


            return $recipe;
        });

        $this->redirectRoute('recipes.show', $recipe);
    }

    // -------------------- sorting handlers --------------------

    public function sortPhotoImages(int $index, int $newPosition): void
    {
        $item = $this->photoImages[$index] ?? null;
        if (!$item) return;

        // Remove and reinsert at new position
        array_splice($this->photoImages, $index, 1);
        array_splice($this->photoImages, $newPosition, 0, [$item]);
    }

    public function sortRecipeImages(int $index, int $newPosition): void
    {
        $item = $this->recipeImages[$index] ?? null;
        if (!$item) return;

        // Remove and reinsert at new position
        array_splice($this->recipeImages, $index, 1);
        array_splice($this->recipeImages, $newPosition, 0, [$item]);
    }
};
