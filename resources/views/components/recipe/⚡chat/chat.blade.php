<div class="mt-12 space-y-4">
    <flux:separator />
    <flux:heading size="lg" class="mt-6">{{ __('Comments') }}</flux:heading>

    {{-- New Comment --}}
    @if(!$showComposer)
        <flux:button variant="filled" class="w-full cursor-pointer" wire:click="$set('showComposer', true)">
            {{ __('Write a comment') }}
        </flux:button>
    @else
        <form wire:submit="post" class="space-y-2">
            <flux:composer wire:model="body" placeholder="{{ __('Write a comment...') }}" label="{{ __('Comment') }}" label:sr-only>
                <x-slot name="actionsTrailing">
                    <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" class="cursor-pointer"/>
                </x-slot>
            </flux:composer>

            <flux:button variant="ghost" size="sm" wire:click="$set('showComposer', false)" class="cursor-pointer">
                {{ __('Cancel') }}
            </flux:button>
        </form>
    @endif

    {{-- Comment List --}}
    <div>
        @forelse ($this->comments as $comment)
            <livewire:recipe.comment-item :comment="$comment" :key="'comment-'.$comment->id" @comment-deleted="$refresh"/>
        @empty
            <flux:text>{{ __('No comments yet.') }}</flux:text>
        @endforelse
    </div>
</div>
