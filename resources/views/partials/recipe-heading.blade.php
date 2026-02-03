<div class="relative mb-6 w-full flex items-start justify-between">
    <div>
        <flux:heading size="xl" level="1">{{ $recipe->title }}</flux:heading>
        @if($recipe->description)
            <flux:text size="lg">{{ $recipe->description }}</flux:text>
        @endif
    </div>

    @can('update', $recipe)
        <div class="flex-shrink-0 ml-4">
            {{-- -------------------- MOBILE -------------------- --}}
            <div class="flex flex-col gap-2 md:hidden">
                {{-- Edit --}}
                <flux:button :href="route('recipes.edit', ['recipe' => $recipe->id])" icon="pencil"/>

                {{-- Copy link --}}
                <div x-data="{ copied: false }">
                    <flux:tooltip toggleable position="left">
                        <flux:button icon="link"
                            @click="
                                navigator.clipboard.writeText(window.location.href);
                                copied = true;
                                setTimeout(() => copied = false, 1500);
                            "
                        />

                        <flux:tooltip.content x-show="copied">{{ __('Copied') }}</flux:tooltip.content>
                    </flux:tooltip>
                </div>
            </div>

            {{-- -------------------- DESKTOP -------------------- --}}
            <div class="hidden md:flex items-center gap-2">
                {{-- Edit --}}
                <flux:button :href="route('recipes.edit', ['recipe' => $recipe->id])" icon="pencil">
                    {{ __('Edit recipe') }}
                </flux:button>

                {{-- Copy link --}}
                <div x-data="{ copied: false }">
                    <flux:tooltip toggleable position="bottom">
                        <flux:button icon="link"
                            @click="
                                navigator.clipboard.writeText(window.location.href);
                                copied = true;
                                setTimeout(() => copied = false, 1500);
                            "
                        >
                            {{ __('Copy link') }}
                        </flux:button>

                        <flux:tooltip.content x-show="copied">{{ __('Copied') }}</flux:tooltip.content>
                    </flux:tooltip>
                </div>
            </div>
        </div>
    @endcan
</div>
