<?php

use App\Models\RecipeBook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    // Modal state
    public bool $showInfoModal = false;
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    // Currently editing / deleting
    public ?RecipeBook $infoCookbook = null;
    public ?RecipeBook $editing = null;
    public ?RecipeBook $deleting = null;

    // Form fields
    public string $title = '';
    public string $subtitle = '';
    // community | public | private - default is public when creating
    public string $visibility = 'public';

    // Sorting state
    public bool $sortingCommunity = false;
    public bool $sortingPersonal = false;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['required', 'string', 'max:255'],
            'visibility' => ['required', 'in:community,public,private'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editing',
            'title',
            'subtitle',
            'visibility',
        ]);
    }

    // -------------------- queries --------------------

    // example in docs for multiple paginators: https://livewire.laravel.com/docs/4.x/pagination#multiple-paginators
    #[Computed]
    public function communityCookbooks(): LengthAwarePaginator
    {
        return RecipeBook::query()
            ->with('user')
            ->withCount('recipes')
            ->where('community', true)
            ->orderBy('position')
            ->paginate(10, pageName: 'community');
    }

    #[Computed]
    public function communityCookbooksAll(): Collection
    {
        return RecipeBook::query()
            ->with('user')
            ->withCount('recipes')
            ->where('community', true)
            ->orderBy('position')
            ->get();
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

    #[Computed]
    public function personalCookbooksAll(): Collection
    {
        return RecipeBook::query()
            ->withCount('recipes')
            ->where('community', false)
            ->where('user_id', auth()->id())
            ->orderBy('position')
            ->get();
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
        $recipeBook->subtitle = $validated['subtitle'];
        $recipeBook->community = $this->visibility === 'community';
        $recipeBook->private = $this->visibility === 'private';
        $recipeBook->user_id ??= auth()->id(); //  set user id (when creating, when it's null)

        $visibilityChanged = !$isCreating && $wasCommunity !== $recipeBook->community;

        // close gap in old scope (community / personal)
        if ($visibilityChanged) {
            RecipeBook::query()
                ->where('community', $wasCommunity)
                ->when(!$wasCommunity, fn (Builder $query) =>
                    $query->where('user_id', auth()->id())
                )
                ->where('position', '>', $recipeBook->position)
                ->decrement('position');
        }

        // Assign position in new scope (community / personal)
        if ($isCreating  || $visibilityChanged) {
            $recipeBook->position = RecipeBook::query()
                    ->where('community', $recipeBook->community)
                    ->when(!$recipeBook->community, fn (Builder $query) =>
                        $query->where('user_id', auth()->id())
                    )
                    ->count();
        }

        $recipeBook->save();

        $this->resetForm();
        $this->showFormModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deleting);

        DB::transaction(function () {
            $position = $this->deleting->position;

            if ($this->deleting->community) {
                RecipeBook::query()
                    ->where('community', true)
                    ->where('position', '>', $position)
                    ->decrement('position');
            } else {
                RecipeBook::query()
                    ->where('community', false)
                    ->where('user_id', $this->deleting->user_id)
                    ->where('position', '>', $position)
                    ->decrement('position');
            }

            $this->deleting->delete();
        });

        $this->deleting = null;
        $this->showDeleteModal = false;
    }

    // -------------------- modal helpers --------------------

    public function openInfoModal(RecipeBook $recipeBook): void
    {
        $this->authorize('view', $recipeBook);

        $this->infoCookbook = $recipeBook->load('user');
        $this->showInfoModal = true;
    }

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
        $this->subtitle = $recipeBook->subtitle;

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

    // -------------------- sorting handlers --------------------

    public function sortCommunity(int $id, int $newPosition): void
    {
        $books = RecipeBook::query()
            ->where('community', true)
            ->orderBy('position')
            ->lockForUpdate()
            ->get();

        $moved = $books->firstWhere('id', $id);
        if (!$moved) return;

        $books = $books->reject(fn ($b) => $b->id === $id)->values();

        $books->splice($newPosition, 0, [$moved]);

        foreach ($books as $index => $book) {
            if ($book->position !== $index) {
                $book->update(['position' => $index]);
            }
        }
    }

    public function sortPersonal(int $id, int $newPosition): void
    {
        $books = RecipeBook::query()
            ->where('community', false)
            ->where('user_id', auth()->id())
            ->orderBy('position')
            ->lockForUpdate()
            ->get();

        $moved = $books->firstWhere('id', $id);
        if (!$moved) return;

        // reject() removes the moved book from the collection, values() resets the array index to have no gaps
        $books = $books->reject(fn ($b) => $b->id === $id)->values();

        // this inserts the moved book at the right position
        $books->splice($newPosition, 0, [$moved]);

        // set the position attribute for all rows necessary
        foreach ($books as $index => $book) {
            if ($book->position !== $index) {
                $book->update(['position' => $index]);
            }
        }
    }
};
