<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ $cookbook->title }}</flux:heading>
        @if($cookbook->subtitle)
            <flux:subheading size="lg">{{ $cookbook->subtitle }}</flux:subheading>
        @endif
    </div>

    <div class="flex-shrink-0 ml-4">
        <flux:button wire:click="openCreateModal" icon="plus" />
    </div>
</div>
