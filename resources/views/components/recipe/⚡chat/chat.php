<?php

use App\Enums\NotificationType;
use App\Models\Comment;
use App\Models\CommentNotification;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public int $recipeId;

    #[Validate('required|string|max:5000')]
    public string $body = '';

    public bool $showComposer = false;

    public function mount(int $recipeId): void
    {
        $this->recipeId = $recipeId;
    }

    #[Computed]
    public function comments(): Collection
    {
        return Comment::query()
            ->where('recipe_id', $this->recipeId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();
    }

    public function post(): void
    {
        $this->validate();

        $comment = Comment::create([
            'recipe_id' => $this->recipeId,
            'user_id'   => auth()->id(),
            'body'      => $this->body,
        ]);

        // Notify the recipe author if they're not the one commenting
        $recipe = Recipe::query()->findOrFail($this->recipeId);
        if ($recipe->user_id !== auth()->id()) {
            CommentNotification::create([
                'user_id'      => $recipe->user_id,
                'triggered_by' => auth()->id(),
                'comment_id'   => $comment->id,
                'type'         => NotificationType::COMMENT,
            ]);
        }

        $this->reset('body');
        $this->showComposer = false;
    }
};
