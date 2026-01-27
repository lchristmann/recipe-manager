<section class="w-full">
    @include('partials.recipe-create-heading')

    @if($cookbook)
        <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            {{ __('Cookbook:') }} {{ $cookbook->title }}
        </div>
    @endif

    {{-- Form will be implemented here later --}}
</section>
