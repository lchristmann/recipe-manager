<section class="w-full max-w-3xl">
    @include('partials.recipe-edit-heading')

    <div class="space-y-6">

        {{-- Cookbook --}}
        @if(!$cookbookUnlocked)
            <flux:input.group label="{{ __('Cookbook') }}">
                <flux:input :value="$recipe->cookbook->title" disabled />
                <flux:button type="button" wire:click="$set('cookbookUnlocked', true)" class="cursor-pointer">
                    {{ __('Change') }}
                </flux:button>
            </flux:input.group>
        @else
            <flux:field>
                <flux:label>{{ __('Cookbook') }}</flux:label>

                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down" class="w-full justify-between">
                        {{ $this->selectedCookbook?->title ?? __('Select cookbook') }}
                    </flux:button>

                    <flux:menu>
                        @if($this->communityCookbooks->isNotEmpty())
                            <flux:menu.group heading="{{ __('Community') }}">
                                @foreach ($this->communityCookbooks as $cb)
                                    <flux:menu.item wire:key="cb-c-{{ $cb->id }}"
                                        wire:click="$set('selectedCookbookId', {{ $cb->id }})">
                                        {{ $cb->title }}
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu.group>
                        @endif

                        @if($this->userCookbooks->isNotEmpty())
                            <flux:menu.group :heading="auth()->user()->name">
                                @foreach ($this->userCookbooks as $cb)
                                    <flux:menu.item wire:key="cb-p-{{ $cb->id }}"
                                        wire:click="$set('selectedCookbookId', {{ $cb->id }})">
                                        {{ $cb->title }}
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu.group>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            </flux:field>
        @endif

        {{-- Title --}}
        <flux:input wire:model="title" label="{{ __('Title') }}" />

        {{-- Links --}}
        <flux:field>
            <flux:label>{{ __('Links') }}</flux:label>
            @foreach ($links as $index => $link)
                <flux:input.group wire:key="link-{{ $index }}">
                    <flux:input wire:model="links.{{ $index }}" placeholder="https://example.com/..." />
                    <flux:button type="button" wire:click="removeLink({{ $index }})" class="cursor-pointer">{{ __('Remove') }}</flux:button>
                </flux:input.group>
                <flux:error name="links.{{ $index }}" />
            @endforeach
        </flux:field>
        <flux:button type="button" wire:click="addLink" class="cursor-pointer">{{ __('Add link') }}</flux:button>

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

        {{-- Photo Images --}}
        <flux:file-upload wire:model="newPhotoFiles" multiple label="{{ __('Photos') }}">
            <flux:file-upload.dropzone heading="{{ __('Drop photos or click to browse') }}" text="{{ __('JPG, PNG, GIF up to 10MB') }}" inline with-progress />
        </flux:file-upload>
        <div class="mt-3 flex flex-col gap-2" wire:sort="sortPhotoImages">
            @foreach ($photoImages as $index => $image)
                <flux:file-item wire:sort:item="{{ $index }}" :heading="$image['heading']" wire:key="{{ $image['key'] }}"
                    :image="$image['preview']" :size="$image['size']">
                    <x-slot name="actions">
                        <flux:file-item.remove wire:click="removePhotoImage({{ $index }})"/>
                    </x-slot>
                </flux:file-item>
            @endforeach
        </div>
        @if($errors->hasAny('photoImages.*.file'))
            <div class="text-red-600 text-sm mt-1">
                {{ collect($errors->get('photoImages.*.file'))
                    ->filter(fn($m) => is_array($m) && count($m))
                    ->flatten()
                    ->first() }}
            </div>
        @endif

        {{-- Recipe Images --}}
        <flux:file-upload wire:model="newRecipeFiles" multiple label="{{ __('Recipe pages') }}">
            <flux:file-upload.dropzone heading="{{ __('Drop recipe images or click to browse') }}" text="{{ __('JPG, PNG, GIF up to 8MB') }}" inline with-progress />
        </flux:file-upload>
        <div class="mt-3 flex flex-col gap-2" wire:sort="sortRecipeImages">
            @foreach ($recipeImages as $index => $image)
                <flux:file-item wire:sort:item="{{ $index }}" :heading="$image['heading']" wire:key="{{ $image['key'] }}"
                    :image="$image['preview']" :size="$image['size']">
                    <x-slot name="actions">
                        <flux:file-item.remove wire:click="removeRecipeImage({{ $index }})"/>
                    </x-slot>
                </flux:file-item>
            @endforeach
        </div>
        @if($errors->hasAny('recipeImages.*.file'))
            <div class="text-red-600 text-sm mt-1">
                {{ collect($errors->get('recipeImages.*.file'))
                    ->filter(fn($m) => is_array($m) && count($m))
                    ->flatten()
                    ->first() }}
            </div>
        @endif

        {{-- Actions --}}
        <div class="pt-4 flex justify-end gap-3">
            <flux:button variant="ghost" :href="route('recipes.show', $recipe)">
                {{ __('Cancel') }}
            </flux:button>

            <flux:button wire:click="save" variant="primary" class="cursor-pointer">
                {{ __('Save changes') }}
            </flux:button>
        </div>

    </div>

    {{-- DELETE MODAL --}}
    <flux:modal wire:model.self="showDeleteModal" title="{{ __('Confirm Deletion') }}" class="md:w-96">
        <div class="space-y-6">
            <p>
                {{ __('Are you sure you want to delete :title?', ['title' => $recipe->title]) }}
            </p>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete" class="cursor-pointer">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
