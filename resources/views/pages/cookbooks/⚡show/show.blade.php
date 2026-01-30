<section class="w-full space-y-6">
    @include('partials.cookbook-heading')

    <div class="flex flex-col sm:flex-row gap-4">
        <flux:input icon="magnifying-glass"
            wire:model.live.debounce.250ms="search"
            placeholder="{{ __('Search recipes...') }}"
            class="w-full sm:w-64"/>

        <flux:pillbox wire:model.live="selectedTags" multiple
              placeholder="{{ __('Filter by tags...') }}"
              class="w-full sm:max-w-lg">
            @foreach ($this->availableTags as $tag)
                <flux:pillbox.option value="{{ $tag->id }}" wire:key="tag-{{ $tag->id }}">
                    {{ $tag->name }}
                </flux:pillbox.option>
            @endforeach
        </flux:pillbox>
    </div>

    <!-- Recipe grid -->
    @if ($this->recipes->isEmpty())
        <div class="py-16">
            <flux:text class="text-center">{{ __('No recipes found.') }}</flux:text>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4">
            @foreach ($this->recipes as $recipe)
                <x-recipe-card :recipe="$recipe" wire:key="recipe-{{ $recipe->id }}" />
            @endforeach
        </div>

        <!-- Infinite scroll trigger -->
        @if ($hasMoreRecipes)
            <div wire:intersect.margin.100%="loadRecipes" class="h-px"></div>
        @endif
    @endif
</section>
