<?php

use App\Models\Cookbook;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    public Cookbook $cookbook;

    // Filters
    public string $search = '';
    public array $selectedTags = [];

    // Infinite scroll state
    public int $page = 1;
    public int $perPage = 48;
    public bool $hasMoreRecipes = true;

    public Collection $recipes;
    // Tags available for this cookbook
    public Collection $availableTags;

    public bool $sorting = false;

    public function mount(Cookbook $cookbook): void
    {
        $this->cookbook = $cookbook;
        $this->recipes = collect();

        // Load all tags present in this cookbook
        $this->availableTags = Tag::query()
            ->whereHas('recipes', fn (Builder $query) => $query->where('cookbook_id', $cookbook->id))
            ->orderBy('name')
            ->get();

        $this->loadRecipes();
    }

    // -------------------- search + pillbox filter + sort toggle --------------------

    public function updatedSearch(): void
    {
        $this->resetRecipes();
    }

    public function updatedSelectedTags(): void
    {
        $this->resetRecipes();
    }

    protected function resetRecipes(): void
    {
        $this->page = 1;
        $this->hasMoreRecipes = true;
        $this->recipes = collect();

        $this->loadRecipes();
    }

    public function toggleSorting(): void
    {
        if (!$this->sorting) {
            // entering sorting mode
            $this->search = '';
            $this->selectedTags = [];
            $this->loadAllRecipesForSorting();
        } else {
            // leaving sorting mode -> restore normal browsing
            $this->resetRecipes();
        }

        $this->sorting = !$this->sorting;
    }

    // -------------------- infinite scroll --------------------

    // docs: https://livewire.laravel.com/docs/4.x/wire-intersect#infinite-scroll
    public function loadRecipes(): void
    {
        if (!$this->hasMoreRecipes) {
            return;
        }

        $query = Recipe::query()
            ->where('cookbook_id', $this->cookbook->id)
            ->with(['photoImages'])
            ->orderBy('position');

        // apply search filter if present
        if ($this->search !== '') {
            $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($this->search) . '%']);
        }

        // apply tag filter if present
        if (!empty($this->selectedTags)) {
            foreach ($this->selectedTags as $tagId) {
                $query->whereHas('tags', fn(Builder $query) => $query->where('tags.id', $tagId));
            }
        }

        $newRecipes = $query
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        if ($newRecipes->count() < $this->perPage) {
            $this->hasMoreRecipes = false;
        }

        if ($newRecipes->isEmpty()) {
            return;
        }

        $this->recipes = $this->recipes->merge($newRecipes);
        $this->page++;
    }

    protected function loadAllRecipesForSorting(): void
    {
        $this->recipes = Recipe::query()
            ->where('cookbook_id', $this->cookbook->id)
            ->with(['photoImages'])
            ->orderBy('position')
            ->get();

        // Disable infinite scroll state
        $this->hasMoreRecipes = false;
        $this->page = 1;
    }

    // -------------------- sorting handlers --------------------

    public function sortRecipe(int $id, int $newPosition): void
    {
        $recipes = $this->recipes->sortBy('position')->values();

        $moved = $recipes->firstWhere('id', $id);
        if (!$moved) return;

        $recipes = $recipes->reject(fn($r) => $r->id === $id)->values();

        $recipes->splice($newPosition, 0, [$moved]);

        foreach ($recipes as $index => $recipe) {
            if ($recipe->position !== $index) {
                Recipe::query()->where('id', $recipe->id)->update(['position' => $index]);
            }
        }

        // Refresh the recipes collection after reordering
        $this->recipes = $recipes;
    }
};
