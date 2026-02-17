@props([
    'recipe',
])

<a
    href="{{ route('recipes.show', $recipe) }}"
    class="group block rounded-md overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 hover:shadow-sm transition"
>
    <div class="aspect-square bg-zinc-100 dark:bg-zinc-800 relative overflow-hidden">
        @php
            $image = $recipe->photoImages->first();
        @endphp

        @if ($image)
            <img
                src="{{ route('recipe-images.show', [$image, 'size' => 600]) }}"
                class="absolute inset-0 w-full h-full object-cover group-hover:scale-103 transition"
            />
        @else
            <div class="absolute inset-0 flex items-center justify-center">
                <flux:icon.book-open-text class="size-10 text-zinc-400"/>
            </div>
        @endif
    </div>

    <div class="p-3">
        <flux:heading size="sm" weight="medium" class="line-clamp-2">
            {{ $recipe->title }}
        </flux:heading>
    </div>
</a>
