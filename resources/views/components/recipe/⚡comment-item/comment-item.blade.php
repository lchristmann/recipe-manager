<div class="p-3 rounded-lg {{ $comment->parent_id ? 'ml-4 pb-0' : '' }}">

    {{-- Header: Avatar + Name + Timestamp --}}
    <div class="flex flex-row sm:items-center gap-2">
        <flux:avatar :initials="$comment->user->initials()" size="xs" class="shrink-0" />

        <div class="flex flex-col gap-0.5 sm:gap-2 sm:flex-row sm:items-center">
            <flux:heading size="sm">{{ $comment->user->name }}</flux:heading>
            <flux:text class="text-sm">{{ $comment->created_at->diffForHumans() }}</flux:text>
        </div>
    </div>

    {{-- Body --}}
    <div class="pl-8 mt-2">
        <flux:text class="whitespace-pre-wrap">{{ $comment->body }}</flux:text>

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
                            <flux:menu.item icon="pencil-square" class="cursor-pointer">Edit</flux:menu.item>
                        @endcan
                        @can('delete', $comment)
                            <flux:menu.item variant="danger" icon="trash" class="cursor-pointer">Delete</flux:menu.item>
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
        <livewire:recipe.comment-item :comment="$reply" :key="'comment-'.$reply->id"/>
    @endforeach
</div>
