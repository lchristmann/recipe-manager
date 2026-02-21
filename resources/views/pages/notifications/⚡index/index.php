<?php

use App\Models\CommentNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $showClearModal = false;

    #[Computed]
    public function notifications(): Collection
    {
        return auth()->user()
            ->commentNotifications()
            ->with(['actor', 'comment'])
            ->latest()
            ->get();
    }

    public function toggleRead(CommentNotification $notification): void
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['read' => ! $notification->read]);

        $this->dispatch('notifications-updated');
    }

    public function openNotification(CommentNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->loadMissing('comment');

        if (!$notification->read) {
            $notification->update(['read' => true]);
            $this->dispatch('notifications-updated');
        }

        return redirect()->to(
            route('recipes.show', $notification->comment->recipe_id)
            . '#comment-' . $notification->comment->id
        );
    }

    public function markAllAsRead(): void
    {
        auth()->user()
            ->unreadCommentNotifications()
            ->update(['read' => true]);

        $this->dispatch('notifications-updated');
    }

    public function confirmClear(): void
    {
        $this->showClearModal = true;
    }

    public function clearAll(): void
    {
        auth()->user()
            ->commentNotifications()
            ->delete();

        $this->showClearModal = false;

        $this->dispatch('notifications-updated');
    }
};
