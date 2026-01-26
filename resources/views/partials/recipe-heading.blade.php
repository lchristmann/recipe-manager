<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ $recipe->title }}</flux:heading>
        @if($recipe->description)
            <flux:text size="lg">{{ $recipe->description }}</flux:text>
        @endif
    </div>

    <div class="flex-shrink-0 ml-4">
        <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $recipe->id }})" />
    </div>
</div>
