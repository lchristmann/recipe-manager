<div class="mt-12 space-y-6">
    <flux:separator />
    <flux:heading size="lg">{{ __('Discussion') }}</flux:heading>

    {{-- New Comment --}}
    <form wire:submit="post">
        <flux:composer wire:model="body" placeholder="{{ __('Write a comment...') }}" label="Comment" label:sr-only>
            <x-slot name="actionsTrailing">
                <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" class="cursor-pointer"/>
            </x-slot>
        </flux:composer>
    </form>

    {{-- Comment List --}}
    <div>
        @forelse ($this->comments as $comment)
            <livewire:recipe.comment-item :comment="$comment" :key="'comment-'.$comment->id"/>
        @empty
            <flux:text>{{ __('No comments yet.') }}</flux:text>
        @endforelse
    </div>
</div>
