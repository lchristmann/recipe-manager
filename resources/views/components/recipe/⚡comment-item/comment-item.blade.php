<div
    id="comment-{{ $comment->id }}"
    x-data="{ highlight: false }"
    x-init="
        if (window.location.hash === '#comment-{{ $comment->id }}') {
            highlight = true;
            setTimeout(() => highlight = false, 1500);
        }
        $el.addEventListener('highlight-comment', () => {
            highlight = true;
            setTimeout(() => highlight = false, 1500);
        });
    "
    :class="highlight ? 'bg-yellow-100 dark:bg-yellow-900' : 'transition-colors duration-3000'"
    class="p-3 rounded-lg {{ $comment->parent_id ? 'ml-4 pb-0' : '' }}"
>

    {{-- Header: Avatar + Name + Timestamp --}}
    <div class="flex flex-row sm:items-center gap-2">
        <flux:avatar :src="$comment->user->profileImageUrl()" :initials="$comment->user->initials()" size="xs" class="shrink-0" />

        <div class="flex flex-col gap-0.5 sm:gap-2 sm:flex-row sm:items-center">
            <flux:heading size="sm">{{ $comment->user->name }}</flux:heading>
            <flux:text class="text-sm">{{ $comment->created_at->diffForHumans() }}</flux:text>
        </div>
    </div>

    {{-- Body --}}
    <div class="pl-8 mt-2">
        @if($editing)
            <form wire:submit="saveEdit" class="space-y-2">
                <flux:composer wire:model="editBody" placeholder="{{ __('Edit comment...') }}" label="{{ __('Edit') }}" label:sr-only>
                    <x-slot name="actionsTrailing">
                        <flux:button type="submit" size="sm" variant="primary" icon="check" class="cursor-pointer"/>
                    </x-slot>
                </flux:composer>
                <flux:button variant="ghost" size="sm" wire:click="cancelEdit" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
            </form>
        @else
            <flux:text class="whitespace-pre-wrap">{{ $comment->body }}</flux:text>
        @endif

        {{-- Actions: thumbs-up, reply, dropdown --}}
        <div class="flex items-center mt-1">
            {{-- Like --}}
            <livewire:recipe.like-button :comment-id="$comment->id" :key="'like-'.$comment->id"/>

            {{-- Reply (only top-level comments) --}}
            @if(is_null($comment->parent_id))
                <flux:button wire:click="$set('replying', true)" variant="subtle" size="sm" icon="arrow-uturn-left" class="[&_svg]:size-4 cursor-pointer"/>
            @endif

            {{-- Dropdown for edit/delete --}}
            @canany(['update', 'delete'], $comment)
                <flux:dropdown>
                    <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" class="cursor-pointer"/>

                    <flux:menu class="min-w-0">
                        @can('update', $comment)
                            <flux:menu.item icon="pencil-square" wire:click="startEdit" class="cursor-pointer">{{ __('Edit') }}</flux:menu.item>
                        @endcan
                        @can('delete', $comment)
                            <flux:menu.item variant="danger" icon="trash" wire:click="openDeleteModal" class="cursor-pointer">{{ __('Delete') }}</flux:menu.item>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            @endcanany
        </div>

        {{-- Reply Composer --}}
        @if($replying)
            <form wire:submit="postReply" class="pt-2 space-y-2">
                <flux:composer wire:model="replyBody" placeholder="{{ __('Write a reply...') }}" label="{{ __('Reply') }}" label:sr-only>
                    <x-slot name="actionsTrailing">
                        <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" class="cursor-pointer"/>
                    </x-slot>
                </flux:composer>
                <flux:button variant="ghost" size="sm" wire:click="cancelReply" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
            </form>
        @endif

    </div>

    {{-- Replies (1 level only) --}}
    @foreach($comment->replies as $reply)
        <livewire:recipe.comment-item :comment="$reply" :key="'comment-'.$reply->id" @comment-deleted="$refresh"/>
    @endforeach

    {{-- DELETE MODAL --}}
    <flux:modal wire:model.self="showDeleteModal" title="{{ __('Confirm Deletion') }}" class="md:w-96">
        <div class="space-y-6">
            <p>{{ __('Are you sure you want to delete this comment?') }}</p>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete" class="cursor-pointer">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
