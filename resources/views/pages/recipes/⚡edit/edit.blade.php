<section class="w-full max-w-3xl">
    @include('partials.recipe-edit-heading')

    <div class="space-y-6">

        {{-- Cookbook (read-only) --}}
        <flux:input :value="$recipe->cookbook->title" disabled label="{{ __('Cookbook') }}"/>

        {{-- Title --}}
        <flux:input wire:model="title" label="{{ __('Title') }}" />

        {{-- Links --}}
        <flux:field>
            <flux:label>{{ __('Links') }}</flux:label>
            @foreach ($links as $index => $link)
                <flux:input.group wire:key="link-{{ $index }}">
                    <flux:input wire:model="links.{{ $index }}" placeholder="https://example.com/..." />
                    <flux:button type="button" wire:click="removeLink({{ $index }})">{{ __('Remove') }}</flux:button>
                </flux:input.group>
                <flux:error name="links.{{ $index }}" />
            @endforeach
        </flux:field>
        <flux:button type="button" wire:click="addLink">{{ __('Add link') }}</flux:button>

        {{-- Description --}}
        <flux:textarea wire:model="description" rows="3" label="{{ __('Description') }}"
           placeholder="{{ __('Short description (optional)') }}"/>

        {{-- Tags --}}
        <flux:pillbox wire:model.live="selectedTags" variant="combobox" multiple label="{{ __('Tags') }}">
            <x-slot name="input">
                <flux:pillbox.input wire:model="tagSearch" placeholder="{{ __('Choose or create tags...') }}"/>
            </x-slot>

            @foreach ($this->allTags as $tag)
                <flux:pillbox.option :value="$tag->id" wire:key="tag-{{ $tag->id }}">
                    {{ $tag->name }}
                </flux:pillbox.option>
            @endforeach

            <flux:pillbox.option.create wire:click="createTag" min-length="2">
                {{ __('Create new') }} "<span wire:text="tagSearch"></span>"
            </flux:pillbox.option.create>
        </flux:pillbox>

        {{-- Ingredients --}}
        <flux:textarea wire:model="ingredients" rows="6" label="{{ __('Ingredients') }}"
           placeholder="{{ __('List ingredients (optional)') }}"/>

        {{-- Instructions --}}
        <flux:textarea wire:model="instructions" rows="6" label="{{ __('Instructions') }}"
           placeholder="{{ __('Preparation steps (optional)') }}"/>

        {{-- Actions --}}
        <div class="pt-4 flex justify-end gap-3">
            <flux:button variant="ghost" :href="route('recipes.show', $recipe)">
                {{ __('Cancel') }}
            </flux:button>

            <flux:button wire:click="save" variant="primary">
                {{ __('Save changes') }}
            </flux:button>
        </div>

    </div>
</section>
