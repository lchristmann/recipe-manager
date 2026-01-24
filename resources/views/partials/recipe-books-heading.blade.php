<div class="relative mb-6 w-full flex flex-col md:flex-row md:items-start md:justify-between">
    <div class="mb-6 md:mb-0">
        <flux:heading size="xl" level="1">{{ __('Cookbooks') }}</flux:heading>
        <flux:subheading size="lg">{{ __('Manage your recipe collections') }}</flux:subheading>
    </div>

    <div class="flex-shrink-0">
        <flux:button wire:click="openCreateModal" icon="plus">
            {{ __('Create cookbook') }}
        </flux:button>
    </div>
</div>

<flux:separator variant="subtle" />
