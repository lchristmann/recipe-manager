<?php

use App\Models\Recipe;
use App\Models\Tag;
use App\Models\TagColor;
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
    #[Validate('required|string')]
    public string $editingColor = 'zinc';

    public bool $showDeleteModal = false;
    public ?int $tagPendingDeletionId = null;

    protected function resetEditing(): void
    {
        $this->editingTagId = null;
        $this->editingName = '';
        $this->editingColor = 'zinc';
    }

    // -------------------- lifecycle hooks --------------------

    public function updatedTab(): void
    {
        $this->resetEditing();
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

        // Load the color for this user/community
        $userId = $this->tab === 'community' ? null : auth()->id();
        $this->editingColor = $tag->colorFor($userId);
    }

    // -------------------- update /  delete --------------------

    public function save(Tag $tag): void
    {
        $this->authorize('update', $tag);

        $this->validate();
        $this->validate(['editingColor' => 'in:' . implode(',', TagColor::COLORS)]);

        // Determine the current user/community scope
        $userId = $this->tab === 'community' ? null : auth()->id();

        // When the tag name didn't change, only create or update its color if necessary
        if ($tag->name === $this->editingName) {
            $tagColor = $tag->colors()->where('user_id', $userId)->first();

            if (!$tagColor) {
                $tag->colors()->create([
                    'user_id' => $userId,
                    'color' => $this->editingColor,
                ]);
            } elseif ($tagColor->color !== $this->editingColor) {
                $tagColor->update(['color' => $this->editingColor]);
            }

            $this->resetEditing();
            return;
        }

        // If we get here, the tag name changed
        DB::transaction(function () use ($tag, $userId) {

            $newTag = Tag::firstOrCreate(['name' => $this->editingName]);

            // Update or create the color for this scope on the new tag
            $newTagColor = $newTag->colors()->where('user_id', $userId)->first();
            if (!$newTagColor) {
                $newTag->colors()->create([
                    'user_id' => $userId,
                    'color' => $this->editingColor,
                ]);
            } elseif ($newTagColor->color !== $this->editingColor) {
                $newTagColor->update(['color' => $this->editingColor]);
            }

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

            if ($recipeIds->isNotEmpty()) {
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
            }
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
