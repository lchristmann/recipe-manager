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

    // -------------------- search + pillbox filter --------------------

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
};
