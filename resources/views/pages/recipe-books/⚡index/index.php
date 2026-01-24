<?php

use App\Models\RecipeBook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    // Modal state
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    // Currently editing / deleting
    public ?RecipeBook $editing = null;
    public ?RecipeBook $deleting = null;

    // Form fields
    public string $title = '';
    // community | public | private - default is public when creating
    public string $visibility = 'public';

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'visibility' => ['required', 'in:community,public,private'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editing',
            'title',
            'visibility',
        ]);
    }

    // -------------------- queries --------------------

    // example in docs for multiple paginators: https://livewire.laravel.com/docs/4.x/pagination#multiple-paginators
    #[Computed]
    public function communityCookbooks(): LengthAwarePaginator
    {
        return RecipeBook::query()
            ->with(['user'])
            ->withCount('recipes')
            ->where('community', true)
            ->orderBy('position')
            ->paginate(10, pageName: 'community');
    }

    #[Computed]
    public function personalCookbooks(): LengthAwarePaginator
    {
        return RecipeBook::query()
            ->withCount('recipes')
            ->where('community', false)
            ->where('user_id', auth()->id())
            ->orderBy('position')
            ->paginate(10, pageName: 'personal');
    }

    // -------------------- create / update / delete --------------------

    public function save(): void
    {
        $isCreating = is_null($this->editing);

        $wasCommunity = $this->editing?->community;

        $this->authorize($isCreating ? 'create' : 'update', $this->editing ?? RecipeBook::class);

        $validated = $this->validate();

        $recipeBook = $this->editing ?? new RecipeBook();

        $recipeBook->title = $validated['title'];
        // visibility variable -> community & private booleans
        $recipeBook->community = $this->visibility === 'community';
        $recipeBook->private = $this->visibility === 'private';

        $visibilityChanged = !$isCreating && $wasCommunity !== $recipeBook->community;

        if ($isCreating  || $visibilityChanged) {
            $recipeBook->user_id ??= auth()->id();

            $recipeBook->position = RecipeBook::query()
                    ->where('community', $recipeBook->community)
                    ->when(!$recipeBook->community, fn (Builder $query) =>
                        $query->where('user_id', auth()->id())
                    )
                    ->max('position') + 1;
        }

        $recipeBook->save();

        $this->resetForm();
        $this->showFormModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deleting);

        $this->deleting->delete();

        $this->deleting = null;
        $this->showDeleteModal = false;
    }

    // -------------------- modal helpers --------------------

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(RecipeBook $recipeBook): void
    {
        $this->authorize('update', $recipeBook);

        $this->editing = $recipeBook;
        $this->title = $recipeBook->title;

        // community & private booleans in database -> visibility variable mapping
        $this->visibility = match (true) {
            $recipeBook->community => 'community',
            $recipeBook->private   => 'private',
            default                => 'public',
        };

        $this->showFormModal = true;
    }

    public function openDeleteModal(RecipeBook $recipeBook): void
    {
        $this->authorize('delete', $recipeBook);

        $this->deleting = $recipeBook;
        $this->showDeleteModal = true;
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
    }
};
