<?php

use App\Models\CommentNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public bool $showClearModal = false;

    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        return auth()->user()
            ->commentNotifications()
            ->with(['actor', 'comment'])
            ->latest()
            ->paginate(10);
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

        $this->resetPage();
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

        $this->resetPage();
    }
};
