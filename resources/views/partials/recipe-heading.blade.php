<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ $recipe->title }}</flux:heading>
        @if($recipe->description)
            <flux:text size="lg">{{ $recipe->description }}</flux:text>
        @endif
    </div>

    @can('update', $recipe)
        <div class="flex-shrink-0 ml-4">
            {{-- Mobile: icon only --}}
            <div class="md:hidden">
                <flux:button :href="route('recipes.edit', ['recipe' => $recipe->id])" icon="pencil" />
            </div>

            {{-- Desktop: icon + text --}}
            <div class="hidden md:block">
                <flux:button :href="route('recipes.edit', ['recipe' => $recipe->id])" icon="pencil">
                    {{ __('Edit recipe') }}
                </flux:button>
            </div>
        </div>
    @endcan
</div>
