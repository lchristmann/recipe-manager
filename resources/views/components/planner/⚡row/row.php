<?php

use App\Models\PlannedRecipe;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public array $plan;
    public string $date;

    public string $mode = 'view';

    public $recipeId = null;
    public string $search = '';

    public function mount(array $plan, string $date): void
    {
        $this->plan = $plan;
        $this->date = $date;

        $this->mode = $plan['mode'] ?? 'view';
        $this->recipeId = $plan['recipe_id'] ?? null;
        $this->search = '';
    }

    #[Computed]
    public function recipes(): Collection
    {
        $cleanSearch = $this->getCleanSearch();

        return Recipe::query()
            ->with('cookbook.user')
            ->whereHas('cookbook', function (Builder $query) {
                $query->where('community', true)
                    ->orWhere('private', false)
                    ->orWhere(function (Builder $q) {
                        $q->where('private', true)
                            ->where('user_id', auth()->id());
                    });
            })
            ->when($cleanSearch, fn (Builder $query) =>
                $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($cleanSearch) . '%'])
            )
            ->orderBy('title')
            ->limit(20)
            ->get()
            ->when(blank($this->search) && $this->recipeId, function (Collection $results) {
                return Recipe::query()
                    ->with('cookbook.user')
                    ->whereKey($this->recipeId)
                    ->whereNotIn('id', $results->pluck('id'))
                    ->get()
                    ->merge($results);
            });
    }

    protected function getCleanSearch(): ?string
    {
        if (!$this->search) return null;

        // Strip the " (community)" or " (User Xyz)" in parentheses at the end
        return trim(preg_replace('/\s*\([^)]+\)$/', '', $this->search));
    }

    public function startEdit(): void
    {
        $this->mode = 'edit';
    }

    public function save(): void
    {
        $recipeId = $this->recipeId ? (int)$this->recipeId : null;
        if (!$recipeId) return;

        PlannedRecipe::updateOrCreate(
            ['id' => $this->plan['id']],
            [
                'user_id' => auth()->id(),
                'recipe_id' => $recipeId,
                'date' => $this->date,
                'position' => 0,
            ]
        );

        $this->dispatch('planner-refresh');
    }

    public function delete(): void
    {
        if ($this->mode === 'create') {
            $this->recipeId = null;
            $this->search = '';
            return;
        }

        if ($this->plan['id']) {
            PlannedRecipe::whereKey($this->plan['id'])->delete();
        }

        $this->dispatch('planner-refresh');
    }
};
