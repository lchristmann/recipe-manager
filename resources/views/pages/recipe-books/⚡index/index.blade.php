<section class="w-full">
    @include('partials.recipe-books-heading')

    {{-- COMMUNITY COOKBOOKS --}}
    <flux:heading size="lg" class="mb-4">
        {{ __('Community Cookbooks') }}
    </flux:heading>

    <flux:table :paginate="$this->communityCookbooks">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Creator') }}</flux:table.column>
            <flux:table.column>{{ __('Recipes') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->communityCookbooks as $cookbook)
                <flux:table.row :key="$cookbook->id">
                    <flux:table.cell>{{ $cookbook->title }}</flux:table.cell>
                    <flux:table.cell>{{ $cookbook->user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $cookbook->recipes_count }}</flux:table.cell>

                    <flux:table.cell>
                        @can('update', $cookbook)
                            <div class="hidden sm:flex justify-end gap-2">
                                <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $cookbook->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button icon="trash" size="sm" wire:click="openDeleteModal({{ $cookbook->id }})">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>

                            <div class="sm:hidden">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil" wire:click="openEditModal({{ $cookbook->id }})">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="trash" wire:click="openDeleteModal({{ $cookbook->id }})">
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- PERSONAL COOKBOOKS --}}
    <flux:heading size="lg" class="mt-10 mb-4">
        {{ __('My Cookbooks') }}
    </flux:heading>

    <flux:table :paginate="$this->personalCookbooks">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Recipes') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->personalCookbooks as $cookbook)
                <flux:table.row :key="$cookbook->id">
                    <flux:table.cell>{{ $cookbook->title }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($cookbook->private)
                            <flux:badge color="zinc" size="sm" inset="top bottom">{{ __('Private') }}</flux:badge>
                        @else
                            <flux:badge color="sky" size="sm" inset="top bottom">{{ __('Public') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $cookbook->recipes_count }}</flux:table.cell>

                    <flux:table.cell>
                        <div class="hidden sm:flex justify-end gap-2">
                            <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $cookbook->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button icon="trash" size="sm" wire:click="openDeleteModal({{ $cookbook->id }})">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>

                        <div class="sm:hidden">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="openEditModal({{ $cookbook->id }})">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="trash" wire:click="openDeleteModal({{ $cookbook->id }})">
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- CREATE / EDIT MODAL --}}
    <flux:modal wire:model.self="showFormModal" title="{{ $editing ? __('Edit Cookbook') : __('Create Cookbook') }}">
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" label="{{ __('Title') }}" />

            <flux:radio.group wire:model="visibility" label="{{ __('Visibility') }}">
                <flux:radio value="community" label="{{ __('Community') }}"
                            description="{{ __('Editable by everyone in the community.') }}" />
                <flux:radio value="public" label="{{ __('Public') }}"
                            description="{{ __('Viewable by everyone, editable only by you.') }}" />
                <flux:radio value="private" label="{{ __('Private') }}"
                            description="{{ __('Only visible and editable by you.') }}" />
            </flux:radio.group>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- DELETE MODAL --}}
    <flux:modal wire:model.self="showDeleteModal" title="{{ __('Confirm Deletion') }}">
        <div class="space-y-6">
            <p>
                {{ __('Are you sure you want to delete :title?', ['title' => $deleting->title ?? '']) }}
            </p>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
