<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ $recipeBook->title }}</flux:heading>
        <flux:subheading size="lg">{{ $recipeBook->subtitle }}</flux:subheading>
    </div>

    <div class="flex-shrink-0 ml-4">
        <flux:button wire:click="openCreateModal" icon="plus" />
    </div>
</div>
