<?php

use App\Models\Comment;
use Livewire\Component;

new class extends Component {
    public int $commentId;

    public int $likesCount = 0;
    public bool $isLiked = false;

    protected function comment(): Comment
    {
        return Comment::findOrFail($this->commentId);
    }

    public function mount(int $commentId): void
    {
        $this->commentId = $commentId;

        $comment = $this->comment();

        $this->likesCount = $comment->likes()->count();
        $this->isLiked = $comment->likes()->where('user_id', auth()->id())->exists();
    }

    public function toggle(): void
    {
        $comment = $this->comment();
        $existing = $comment->likes()->where('user_id', auth()->id())->first();

        if ($existing) {
            $existing->delete();
            $this->likesCount--;
            $this->isLiked = false;
        } else {
            $comment->likes()->create(['user_id' => auth()->id()]);
            $this->likesCount++;
            $this->isLiked = true;
        }
    }
};
?>

<flux:button wire:click="toggle" variant="ghost" size="sm" inset="left" class="gap-2 [&>span]:flex [&>span]:items-center [&>span]:gap-2 cursor-pointer">
    <flux:icon.hand-thumb-up name="hand-thumb-up" variant="{{ $isLiked ? 'solid' : 'outline' }}"
         class="size-4 {{ $isLiked ? 'text-accent-content' : 'text-zinc-400' }} [&_path]:stroke-[2.25]"/>
    <flux:text class="text-sm tabular-nums">{{ $likesCount }}</flux:text>
</flux:button>
