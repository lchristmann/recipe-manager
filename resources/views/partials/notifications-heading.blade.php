<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ __('Notifications') }}</flux:heading>
        <flux:text size="lg"> {{ __('Stay up to date with activity on your recipes and comments.') }}</flux:text>
    </div>

    <div class="flex-shrink-0 ml-4">
        {{-- MOBILE --}}
        <div class="flex flex-col gap-2 md:hidden">
            <flux:button wire:click="markAllAsRead" icon="check-check" class="cursor-pointer" />
            <flux:button wire:click="confirmClear" icon="trash" class="cursor-pointer" />
        </div>

        {{-- DESKTOP --}}
        <div class="hidden md:flex items-center gap-2">
            <flux:button wire:click="markAllAsRead" icon="check-check" class="cursor-pointer">{{ __('Mark all as read') }}</flux:button>
            <flux:button wire:click="confirmClear" icon="trash" class="cursor-pointer">{{ __('Clear inbox') }}</flux:button>
        </div>
    </div>
</div>
