<?php

use App\Models\Comment;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Comment $comment;

    public bool $replying = false;
    public bool $editing = false;
    public bool $showDeleteModal = false;

    #[Validate('required|string|max:5000')]
    public string $replyBody = '';

    #[Validate('required|string|max:5000')]
    public string $editBody = '';

    public function mount(Comment $comment): void
    {
        $this->comment = $comment;
        $this->editBody = $comment->body;
    }

    // -------------------- Replying --------------------

    public function cancelReply(): void
    {
        $this->reset(['replying', 'replyBody']);
    }

    public function postReply(): void
    {
        $this->validateOnly('replyBody');

        Comment::create([
            'recipe_id' => $this->comment->recipe_id,
            'user_id'   => auth()->id(),
            'parent_id' => $this->comment->id,
            'body'      => $this->replyBody,
        ]);

        $this->reset(['replying', 'replyBody']);

        // Refresh replies
        $this->comment->load('replies.user');
    }

    // -------------------- Edit --------------------

    public function startEdit(): void
    {
        $this->editing = true;
        $this->editBody = $this->comment->body;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->editBody = $this->comment->body;
    }

    public function saveEdit(): void
    {
        $this->authorize('update', $this->comment);

        $this->validateOnly('editBody');

        $this->comment->update([
            'body' => $this->editBody,
        ]);

        $this->editing = false;
        $this->comment->refresh();
    }

    // -------------------- Delete --------------------

    public function openDeleteModal(): void
    {
        $this->authorize('delete', $this->comment);

        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->comment);

        $this->comment->delete();

        $this->showDeleteModal = false;

        // Listen to this in the parent component (either CommentItem or Chat):
        // https://livewire.laravel.com/docs/4.x/events#listening-for-events-from-specific-child-components
        $this->dispatch('comment-deleted');
    }
};
