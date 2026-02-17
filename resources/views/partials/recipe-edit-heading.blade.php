<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ $recipe->title }}</flux:heading>
        @if($recipe->description)
            <flux:text size="lg">{{ $recipe->description }}</flux:text>
        @endif
    </div>

    @can('delete', $recipe)
        <div class="flex-shrink-0 ml-4">
            {{-- Mobile: icon only --}}
            <div class="md:hidden">
                <flux:button icon="trash" wire:click="openDeleteModal({{ $recipe->id }})" class="cursor-pointer"/>
            </div>

            {{-- Desktop: icon + text --}}
            <div class="hidden md:block">
                <flux:button icon="trash" wire:click="openDeleteModal({{ $recipe->id }})" class="cursor-pointer">
                    {{ __('Delete recipe') }}
                </flux:button>
            </div>
        </div>
    @endcan
</div>
