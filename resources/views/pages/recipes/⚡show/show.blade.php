<section class="w-full max-w-5xl space-y-6">

    {{-- Heading --}}
    @include('partials.recipe-heading')

    {{-- Tags --}}
    @if($recipe->tags->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @php
                $userId = $recipe->cookbook->community ? null : $recipe->cookbook->user_id;
            @endphp

            @foreach($recipe->tags as $tag)
                <flux:badge wire:key="tag-{{ $tag->id }}" color="{{ $tag->colorFor($userId) }}">{{ $tag->name }}</flux:badge>
            @endforeach
        </div>
    @endif

    {{-- Links --}}
    @if($recipe->links->isNotEmpty())
        <flux:text class="text-sm text-gray-600">
            @foreach($recipe->links as $index => $link)
                <flux:link href="{{ $link->url }}" external>{{ $link->domain }}</flux:link>
                @if(!$loop->last) | @endif
            @endforeach
        </flux:text>
    @endif

    {{-- Photo Images --}}
    @if($recipe->photoImages->isNotEmpty())
        {{-- Mobile slideshow --}}
        <div class="md:hidden pswp-gallery" x-data="{ current: 0, total: {{ $recipe->photoImages->count() }} }">
            <div class="relative w-full h-52 overflow-hidden rounded-md">
                @foreach($recipe->photoImages as $index => $photo)
                    <a
                        x-show="current === {{ $index }}"
                        x-transition
                        href="{{ route('recipe-images.show', $photo) }}"
                        data-pswp-width="{{ $photo->width }}"
                        data-pswp-height="{{ $photo->height }}"
                        data-cropped="true"
                        target="_blank"
                    >
                        <img src="{{ route('recipe-images.show', [$photo, 'size' => 1200]) }}" class="w-full h-full object-cover"/>
                    </a>
                @endforeach
                @if($recipe->photoImages->count() > 1)
                    <button x-on:click="current = (current - 1 + total) % total"
                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/30 text-white rounded-full p-2">
                        ‹
                    </button>
                    <button x-on:click="current = (current + 1) % total"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/30 text-white rounded-full p-2">
                        ›
                    </button>
                @endif
            </div>
        </div>

        {{-- Desktop grid --}}
        <div class="hidden md:grid md:grid-cols-3 lg:grid-cols-4 gap-4 pswp-gallery">
            @foreach($recipe->photoImages as $photo)
                <a
                    href="{{ route('recipe-images.show', $photo) }}"
                    data-pswp-width="{{ $photo->width }}"
                    data-pswp-height="{{ $photo->height }}"
                    data-cropped="true"
                    target="_blank"
                >
                    <img src="{{ route('recipe-images.show', [$photo, 'size' => 1200]) }}" class="w-full h-48 object-cover rounded-md cursor-zoom-in"/>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Ingredients --}}
    @if($recipe->ingredients)
        <div>
            <flux:heading size="md">{{ __('Ingredients') }}</flux:heading>
            <flux:text class="whitespace-pre-wrap">{{ $recipe->ingredients }}</flux:text>
        </div>
    @endif

    {{-- Instructions --}}
    @if($recipe->instructions)
        <div>
            <flux:heading size="md">{{ __('Instructions') }}</flux:heading>
            <flux:text class="whitespace-pre-wrap">{{ $recipe->instructions }}</flux:text>
        </div>
    @endif

    {{-- Recipe Images --}}
    @if($recipe->recipeImages->isNotEmpty())
        {{-- Mobile full-width --}}
        <div class="md:hidden flex flex-col gap-4 pswp-gallery">
            @foreach($recipe->recipeImages as $photo)
                <a
                    href="{{ route('recipe-images.show', $photo) }}"
                    data-pswp-width="{{ $photo->width }}"
                    data-pswp-height="{{ $photo->height }}"
                    target="_blank"
                >
                    <img src="{{ route('recipe-images.show', [$photo, 'size' => 1200]) }}" class="w-full h-auto rounded-md" />
                </a>
            @endforeach
        </div>

        {{-- Desktop --}}
        <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-4 pswp-gallery">
            @foreach($recipe->recipeImages as $photo)
                <a
                    href="{{ route('recipe-images.show', $photo) }}"
                    data-pswp-width="{{ $photo->width }}"
                    data-pswp-height="{{ $photo->height }}"
                    data-cropped="true"
                    target="_blank"
                >
                    <img src="{{ route('recipe-images.show', [$photo, 'size' => 1200]) }}" class="w-full h-64 object-cover rounded-md cursor-zoom-in"/>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Recipe Chat --}}
    <livewire:recipe.chat defer :recipe-id="$recipe->id" />

</section>
