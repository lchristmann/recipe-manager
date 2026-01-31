<?php

use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
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
        ];
    }

    public function messages(): array
    {
        return [
            'links.*.url' => __('Link #:position must be a valid URL.'),
            'links.*.max' => __('Link #:position may not exceed :max characters.'),
        ];
    }

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
        });

        $this->redirectRoute('recipes.show', $this->recipe);
    }
};
