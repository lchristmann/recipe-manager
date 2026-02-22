@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Recipe Manager" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="size-6 text-zinc-800 dark:text-zinc-100" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Recipe Manager" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="size-6 text-zinc-800 dark:text-zinc-100" />
        </x-slot>
    </flux:brand>
@endif
