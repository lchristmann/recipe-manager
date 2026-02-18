<?php

use App\Models\Comment;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Comment $comment;

    public bool $replying = false;

    #[Validate('required|string|max:5000')]
    public string $replyBody = '';

    public function mount(Comment $comment): void
    {
        $this->comment = $comment;
    }

    public function cancelReply(): void
    {
        $this->reset(['replying', 'replyBody']);
    }

    public function postReply(): void
    {
        $this->validate();

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
};
