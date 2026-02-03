<?php

use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Url(keep: true)]
    public string $tab = 'community'; // 'community' | 'personal'

    #[Validate('required|exists:tags,id')]
    public ?int $editingTagId = null;
    #[Validate('required|string|max:255')]
    public string $editingName = '';

    public bool $showDeleteModal = false;
    public ?int $tagPendingDeletionId = null;

    protected function resetEditing(): void
    {
        $this->editingTagId = null;
        $this->editingName = '';
    }

    // -------------------- queries --------------------

    #[Computed]
    public function communityTags(): Collection
    {
        return Tag::query()
            ->whereHas('recipes', function (Builder $query) {
                $query->whereHas('cookbook', function (Builder $q) {
                    $q->where('community', true);
                });
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function personalTags(): Collection
    {
        return Tag::query()
            ->whereHas('recipes', function (Builder $query) {
                $query->whereHas('cookbook', function (Builder $q) {
                    $q->where('community', false)
                        ->where('user_id', auth()->id());
                });
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function tagPendingDeletion(): ?Tag
    {
        return $this->tagPendingDeletionId ? Tag::find($this->tagPendingDeletionId) : null;
    }

    // -------------------- helper methods --------------------

    public function editTag(Tag $tag): void
    {
        $this->authorize('update', $tag);

        $this->editingTagId = $tag->id;
        $this->editingName = $tag->name;
    }

    // -------------------- update /  delete --------------------

    public function save(Tag $tag): void
    {
        $this->authorize('update', $tag);

        $this->validate();

        if ($tag->name === $this->editingName) {
            $this->resetEditing();
            return;
        }

        DB::transaction(function () use ($tag) {

            $newTag = Tag::firstOrCreate(['name' => $this->editingName]);

            // Select recipes with that tag in the active tab's scope
            $recipeIds = Recipe::query()
                ->whereHas('tags', fn(Builder $q) => $q->where('tags.id', $tag->id))
                ->whereHas('cookbook', function (Builder $query) {
                    if ($this->tab === 'community') {
                        $query->where('community', true);
                    } else {
                        $query->where('community', false)
                            ->where('user_id', auth()->id());
                    }
                })
                ->pluck('id');

            if ($recipeIds->isEmpty()) return;

            // Attach new tag to those recipes
            DB::table('recipe_tag')
                ->whereIn('recipe_id', $recipeIds)
                ->insertOrIgnore(
                    $recipeIds->map(fn($id) => [
                        'recipe_id' => $id,
                        'tag_id' => $newTag->id,
                    ])->all()
                );

            // Detach old tag from recipes
            DB::table('recipe_tag')
                ->whereIn('recipe_id', $recipeIds)
                ->where('tag_id', $tag->id)
                ->delete();
        });

        $this->resetEditing();
    }

    public function delete(): void
    {
        if (!$this->tagPendingDeletionId) return;

        $tag = Tag::findOrFail($this->tagPendingDeletionId);

        $this->authorize('delete', $tag);

        DB::transaction(function () use ($tag) {

            $recipeIds = Recipe::query()
                ->whereHas('tags', fn($q) => $q->where('tags.id', $tag->id))
                ->whereHas('cookbook', function ($q) {
                    if ($this->tab === 'community') {
                        $q->where('community', true);
                    } else {
                        $q->where('community', false)
                            ->where('user_id', auth()->id());
                    }
                })
                ->pluck('id');

            if ($recipeIds->isEmpty()) return;

            DB::table('recipe_tag')
                ->whereIn('recipe_id', $recipeIds)
                ->where('tag_id', $tag->id)
                ->delete();
        });

        $this->resetDeleteModal();
    }

    // -------------------- modal helpers --------------------

    public function openDeleteModal(Tag $tag): void
    {
        $this->authorize('delete', $tag);

        $this->tagPendingDeletionId = $tag->id;
        $this->showDeleteModal = true;
    }

    protected function resetDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->tagPendingDeletionId = null;

        $this->resetEditing();
    }
};
