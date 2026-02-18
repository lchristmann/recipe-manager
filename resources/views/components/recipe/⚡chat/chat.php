<?php

use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public int $recipeId;

    #[Validate('required|string|max:5000')]
    public string $body = '';

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

        Comment::create([
            'recipe_id' => $this->recipeId,
            'user_id'   => auth()->id(),
            'body'      => $this->body,
        ]);

        $this->reset('body');
    }
};
