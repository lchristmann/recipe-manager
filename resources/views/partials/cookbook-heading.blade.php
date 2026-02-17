<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ $cookbook->title }}</flux:heading>
        @if($cookbook->subtitle)
            <flux:subheading size="lg">{{ $cookbook->subtitle }}</flux:subheading>
        @endif
    </div>

    @can('update', $cookbook)
        <div class="flex-shrink-0 ml-4">
            {{-- Mobile: icon only --}}
            <div class="md:hidden">
                <flux:button :href="route('recipes.create', ['cookbook' => $cookbook->id])" icon="plus" />
            </div>

            {{-- Desktop: icon + text --}}
            <div class="hidden md:flex items-center gap-2">
                <flux:button :href="route('recipes.create', ['cookbook' => $cookbook->id])" icon="plus">
                    {{ __('Create recipe') }}
                </flux:button>

                <flux:button icon="arrows-right-left" wire:click="toggleSorting" class="cursor-pointer">
                    {{ $sorting ? __('Done') : __('Reorder') }}
                </flux:button>
            </div>
        </div>
    @endcan
</div>
