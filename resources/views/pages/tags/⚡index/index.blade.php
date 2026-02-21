<section class="w-full max-w-3xl">
    @include('partials.tags-heading')

    <div class="space-y-6">
        <flux:tab.group>
            <flux:tabs wire:model.live="tab">
                <flux:tab name="community">{{ __('Community') }}</flux:tab>
                <flux:tab name="personal">{{ __('Personal') }}</flux:tab>
            </flux:tabs>

            {{-- -------------------- COMMUNITY -------------------- --}}
            <flux:tab.panel name="community" class="space-y-3">
                @forelse ($this->communityTags as $tag)
                    <div class="flex items-start gap-2" wire:key="c-tag-{{ $tag->id }}">
                        <flux:input.group class="flex-1" wire:key="c-tag-group-{{ $tag->id }}">
                            @if ($editingTagId === $tag->id)
                                {{-- Tag name --}}
                                <flux:input wire:model="editingName" wire:key="c-tag-input-{{ $tag->id }}"/>

                                {{-- Tag color picker --}}
                                <flux:select wire:model="editingColor" wire:key="c-tag-select-{{ $tag->id }}"
                                     variant="listbox" placeholder="Pick color...">
                                    @foreach(\App\Models\TagColor::COLORS as $color)
                                        <flux:select.option value="{{ $color }}">
                                            <div class="flex items-center gap-2">
                                                <flux:badge color="{{ $color }}">{{ __(ucfirst($color)) }}</flux:badge>
                                            </div>
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                {{-- Save button --}}
                                <flux:button type="button" wire:click="save({{ $tag->id }})" wire:key="c-tag-button-{{ $tag->id }}" class="cursor-pointer">
                                    {{ __('Save') }}
                                </flux:button>
                            @else
                                <flux:input :value="$tag->name" disabled />
                                <flux:button type="button" wire:click="editTag({{ $tag->id }})" class="cursor-pointer">
                                    {{ __('Edit') }}
                                </flux:button>
                            @endif
                        </flux:input.group>

                        <flux:button icon="trash" wire:click="openDeleteModal({{ $tag->id }})" class="cursor-pointer"/>
                    </div>
                @empty
                    <flux:text>{{ __('No tags on community recipes yet.') }}</flux:text>
                @endforelse
            </flux:tab.panel>

            {{-- -------------------- PERSONAL -------------------- --}}
            <flux:tab.panel name="personal" class="space-y-3">
                @forelse ($this->personalTags as $tag)
                    <div class="flex items-start gap-2" wire:key="p-tag-{{ $tag->id }}">
                        <flux:input.group class="flex-1" wire:key="p-tag-group-{{ $tag->id }}">
                            @if ($editingTagId === $tag->id)
                                {{-- Tag name --}}
                                <flux:input wire:model="editingName" wire:key="p-tag-input-{{ $tag->id }}"/>

                                {{-- Tag color picker --}}
                                <flux:select wire:model="editingColor" wire:key="c-tag-select-{{ $tag->id }}"
                                     variant="listbox" placeholder="Pick color...">
                                    @foreach(\App\Models\TagColor::COLORS as $color)
                                        <flux:select.option value="{{ $color }}">
                                            <div class="flex items-center gap-2">
                                                <flux:badge color="{{ $color }}">{{ __(ucfirst($color)) }}</flux:badge>
                                            </div>
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                {{-- Save button --}}
                                <flux:button type="button" wire:click="save({{ $tag->id }})" wire:key="p-tag-button-{{ $tag->id }}" class="cursor-pointer">
                                    {{ __('Save') }}
                                </flux:button>
                            @else
                                <flux:input :value="$tag->name" disabled />
                                <flux:button type="button" wire:click="editTag({{ $tag->id }})" class="cursor-pointer">
                                    {{ __('Edit') }}
                                </flux:button>
                            @endif
                        </flux:input.group>

                        <flux:button icon="trash" wire:click="openDeleteModal({{ $tag->id }})" class="cursor-pointer"/>
                    </div>
                @empty
                    <flux:text>{{ __('No tags on personal recipes yet.') }}</flux:text>
                @endforelse
            </flux:tab.panel>
        </flux:tab.group>
    </div>

    {{-- DELETE MODAL --}}
    <flux:modal wire:model.self="showDeleteModal" title="{{ __('Confirm Deletion') }}" class="md:w-96">
        <div class="space-y-6">
            @if ($this->tagPendingDeletion)
                <p>
                    {{ __('Are you sure you want to remove the tag ":tag" from all :scope?', [
                        'tag'   => $this->tagPendingDeletion->name,
                        'scope' => $tab === 'community' ? __('community recipes') : __('personal recipes'),
                    ]) }}
                </p>
            @endif

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
