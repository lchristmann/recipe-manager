<?php

use App\Models\Cookbook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
    public ?Cookbook $infoCookbook = null;
    public ?Cookbook $editing = null;
    public ?Cookbook $deleting = null;

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
            'subtitle' => ['string', 'max:255'],
            'visibility' => ['required', Rule::in(['community', 'public', 'private'])],
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
        return Cookbook::query()
            ->with('user')
            ->withCount('recipes')
            ->where('community', true)
            ->orderBy('position')
            ->paginate(10, pageName: 'community');
    }

    #[Computed]
    public function communityCookbooksAll(): Collection
    {
        return Cookbook::query()
            ->with('user')
            ->withCount('recipes')
            ->where('community', true)
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function personalCookbooks(): LengthAwarePaginator
    {
        return Cookbook::query()
            ->withCount('recipes')
            ->where('community', false)
            ->where('user_id', auth()->id())
            ->orderBy('position')
            ->paginate(10, pageName: 'personal');
    }

    #[Computed]
    public function personalCookbooksAll(): Collection
    {
        return Cookbook::query()
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

        $this->authorize($isCreating ? 'create' : 'update', $this->editing ?? Cookbook::class);

        $validated = $this->validate();

        $cookbook = $this->editing ?? new Cookbook();

        $cookbook->title = $validated['title'];
        $cookbook->subtitle = $validated['subtitle'];
        $cookbook->community = $this->visibility === 'community';
        $cookbook->private = $this->visibility === 'private';
        $cookbook->user_id ??= auth()->id(); //  set user id (when creating, when it's null)

        $visibilityChanged = !$isCreating && $wasCommunity !== $cookbook->community;

        // close gap in old scope (community / personal)
        if ($visibilityChanged) {
            Cookbook::query()
                ->where('community', $wasCommunity)
                ->when(!$wasCommunity, fn (Builder $query) =>
                    $query->where('user_id', auth()->id())
                )
                ->where('position', '>', $cookbook->position)
                ->decrement('position');
        }

        // Assign position in new scope (community / personal)
        if ($isCreating  || $visibilityChanged) {
            $cookbook->position = Cookbook::query()
                    ->where('community', $cookbook->community)
                    ->when(!$cookbook->community, fn (Builder $query) =>
                        $query->where('user_id', auth()->id())
                    )
                    ->count();
        }

        $cookbook->save();

        $this->dispatch('cookbooks-changed');

        $this->resetForm();
        $this->showFormModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deleting);

        DB::transaction(function () {
            $position = $this->deleting->position;

            if ($this->deleting->community) {
                Cookbook::query()
                    ->where('community', true)
                    ->where('position', '>', $position)
                    ->decrement('position');
            } else {
                Cookbook::query()
                    ->where('community', false)
                    ->where('user_id', $this->deleting->user_id)
                    ->where('position', '>', $position)
                    ->decrement('position');
            }

            $this->deleting->delete();
        });

        $this->dispatch('cookbooks-changed');

        $this->deleting = null;
        $this->showDeleteModal = false;
    }

    // -------------------- modal helpers --------------------

    public function openInfoModal(Cookbook $cookbook): void
    {
        $this->authorize('view', $cookbook);

        $this->infoCookbook = $cookbook->load('user');
        $this->showInfoModal = true;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(Cookbook $cookbook): void
    {
        $this->authorize('update', $cookbook);

        $this->editing = $cookbook;
        $this->title = $cookbook->title;
        $this->subtitle = $cookbook->subtitle;

        // community & private booleans in database -> visibility variable mapping
        $this->visibility = match (true) {
            $cookbook->community => 'community',
            $cookbook->private   => 'private',
            default                => 'public',
        };

        $this->showFormModal = true;
    }

    public function openDeleteModal(Cookbook $cookbook): void
    {
        $this->authorize('delete', $cookbook);

        $this->deleting = $cookbook;
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
        $books = Cookbook::query()
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

        $this->dispatch('cookbooks-changed');
    }

    public function sortPersonal(int $id, int $newPosition): void
    {
        $books = Cookbook::query()
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

        $this->dispatch('cookbooks-changed');
    }
};
