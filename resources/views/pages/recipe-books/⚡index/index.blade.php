@php use Illuminate\Support\Str; @endphp

<section class="w-full">
    @include('partials.recipe-books-heading')

    {{-- -------------------- COMMUNITY COOKBOOKS -------------------- --}}
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">{{ __('Community Cookbooks') }}</flux:heading>
        <flux:button icon="arrows-right-left" wire:click="$toggle('sortingCommunity')">
            {{ $sortingCommunity ? __('Done') : __('Reorder') }}
        </flux:button>
    </div>

    {{-- SORTABLE TABLE --}}
    <table class="w-full mb-4" x-show="$wire.sortingCommunity">
        <flux:table.columns>
            <flux:table.column class="w-6"/>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Subtitle') }}</flux:table.column>
            <flux:table.column>
                <flux:icon.sigma class="size-4 -ml-0.5"/>
            </flux:table.column>
            <flux:table.column/>
        </flux:table.columns>

        <tbody wire:sort="sortCommunity">
        @foreach ($this->communityCookbooksAll as $cookbook)
            <tr wire:sort:item="{{ $cookbook->id }}" :key="$cookbook->id">
                <flux:table.cell class="w-6 cursor-grab " wire:sort:handle>
                    <flux:icon name="bars-2"/>
                </flux:table.cell>
                <flux:table.cell>{{ $cookbook->title }}</flux:table.cell>
                <flux:table.cell class="hidden sm:table-cell">{{ Str::limit($cookbook->subtitle, 25) }}</flux:table.cell>
                <flux:table.cell>{{ $cookbook->recipes_count }}</flux:table.cell>
                <flux:table.cell>
                    @can('update', $cookbook)
                        <div class="hidden sm:flex justify-end gap-2">
                            <flux:button icon="information-circle" size="sm" wire:click="openInfoModal({{ $cookbook->id }})">
                                {{ __('Info') }}
                            </flux:button>
                            <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $cookbook->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button icon="trash" size="sm" wire:click="openDeleteModal({{ $cookbook->id }})">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>

                        <div class="sm:hidden">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>
                                <flux:menu>
                                    <flux:menu.item icon="information-circle" wire:click="openInfoModal({{ $cookbook->id }})">
                                        {{ __('Info') }}
                                    </flux:menu.item>
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
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- PAGINATED FLUX TABLE --}}
    <flux:table :paginate="$this->communityCookbooks" x-show="!$wire.sortingCommunity">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Subtitle') }}</flux:table.column>
            <flux:table.column>
                <flux:icon.sigma class="size-4 -ml-0.5"/>
            </flux:table.column>
            <flux:table.column/>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->communityCookbooks as $cookbook)
                <flux:table.row :key="$cookbook->id">
                    <flux:table.cell>{{ $cookbook->title }}</flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ Str::limit($cookbook->subtitle, 25) }}</flux:table.cell>
                    <flux:table.cell>{{ $cookbook->recipes_count }}</flux:table.cell>
                    <flux:table.cell>
                        @can('update', $cookbook)
                            <div class="hidden sm:flex justify-end gap-2">
                                <flux:button icon="information-circle" size="sm" wire:click="openInfoModal({{ $cookbook->id }})">
                                    {{ __('Info') }}
                                </flux:button>
                                <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $cookbook->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button icon="trash" size="sm" wire:click="openDeleteModal({{ $cookbook->id }})">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>

                            <div class="sm:hidden">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>
                                    <flux:menu>
                                        <flux:menu.item icon="information-circle" wire:click="openInfoModal({{ $cookbook->id }})">
                                            {{ __('Info') }}
                                        </flux:menu.item>
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

    {{-- -------------------- PERSONAL COOKBOOKS -------------------- --}}
    <div class="flex items-center justify-between mt-10 mb-4">
        <flux:heading size="lg">{{ __('My Cookbooks') }}</flux:heading>
        <flux:button icon="arrows-right-left" wire:click="$toggle('sortingPersonal')">
            {{ $sortingPersonal ? __('Done') : __('Reorder') }}
        </flux:button>
    </div>

    {{-- SORTABLE TABLE --}}
    <table class="w-full mb-4" x-show="$wire.sortingPersonal">
        <flux:table.columns>
            <flux:table.column class="w-6"/>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Subtitle') }}</flux:table.column>
            <flux:table.column>
                <flux:icon.sigma class="size-4 -ml-0.5"/>
            </flux:table.column>
            <flux:table.column/>
        </flux:table.columns>

        <tbody wire:sort="sortPersonal">
        @foreach ($this->personalCookbooksAll as $cookbook)
            <tr wire:sort:item="{{ $cookbook->id }}" :key="$cookbook->id">
                <flux:table.cell class="w-6 cursor-grab " wire:sort:handle>
                    <flux:icon name="bars-2"/>
                </flux:table.cell>
                <flux:table.cell>
                    <div class="flex items-center gap-2">
                        @if ($cookbook->private)
                            <flux:icon.lock-closed class="size-4"/>
                        @else
                            <flux:icon.eye class="size-4" />
                        @endif

                        <span>{{ $cookbook->title }}</span>
                    </div>
                </flux:table.cell>
                <flux:table.cell class="hidden sm:table-cell">{{ Str::limit($cookbook->subtitle, 25) }}</flux:table.cell>
                <flux:table.cell>{{ $cookbook->recipes_count }}</flux:table.cell>
                <flux:table.cell>
                    @can('update', $cookbook)
                        <div class="hidden sm:flex justify-end gap-2">
                            <flux:button icon="information-circle" size="sm" wire:click="openInfoModal({{ $cookbook->id }})">
                                {{ __('Info') }}
                            </flux:button>
                            <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $cookbook->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button icon="trash" size="sm" wire:click="openDeleteModal({{ $cookbook->id }})">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>

                        <div class="sm:hidden">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>
                                <flux:menu>
                                    <flux:menu.item icon="information-circle" wire:click="openInfoModal({{ $cookbook->id }})">
                                        {{ __('Info') }}
                                    </flux:menu.item>
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
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- PAGINATED FLUX TABLE --}}
    <flux:table :paginate="$this->personalCookbooks" x-show="!$wire.sortingPersonal">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Subtitle') }}</flux:table.column>
            <flux:table.column>
                <flux:icon.sigma class="size-4 -ml-0.5"/>
            </flux:table.column>
            <flux:table.column/>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->personalCookbooks as $cookbook)
                <flux:table.row :key="$cookbook->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            @if ($cookbook->private)
                                <flux:icon.lock-closed class="size-4"/>
                            @else
                                <flux:icon.eye class="size-4" />
                            @endif

                            <span>{{ $cookbook->title }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ Str::limit($cookbook->subtitle, 25) }}</flux:table.cell>
                    <flux:table.cell>{{ $cookbook->recipes_count }}</flux:table.cell>
                    <flux:table.cell>
                        @can('update', $cookbook)
                            <div class="hidden sm:flex justify-end gap-2">
                                <flux:button icon="information-circle" size="sm" wire:click="openInfoModal({{ $cookbook->id }})">
                                    {{ __('Info') }}
                                </flux:button>
                                <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $cookbook->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button icon="trash" size="sm" wire:click="openDeleteModal({{ $cookbook->id }})">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>

                            <div class="sm:hidden">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>
                                    <flux:menu>
                                        <flux:menu.item icon="information-circle" wire:click="openInfoModal({{ $cookbook->id }})">
                                            {{ __('Info') }}
                                        </flux:menu.item>
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

    {{-- INFO MODAL --}}
    <flux:modal wire:model.self="showInfoModal" title="{{ __('Cookbook Info') }}">
        @if ($infoCookbook)
            <div class="space-y-4 text-sm">
                <div>
                    <strong>{{ __('Title') }}:</strong>
                    {{ $infoCookbook->title }}
                </div>

                <div>
                    <strong>{{ __('Subtitle') }}:</strong>
                    {{ $infoCookbook->subtitle ?: '—' }}
                </div>

                <div>
                    <strong>{{ __('Owner') }}:</strong>
                    {{ $infoCookbook->user->name }}
                </div>

                <div>
                    <strong>{{ __('Created') }}:</strong>
                    {{ $infoCookbook->created_at->translatedFormat('F j, Y') }}
                </div>

                <div>
                    <strong>{{ __('Last updated') }}:</strong>
                    {{ $infoCookbook->updated_at->translatedFormat('F j, Y') }}
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <flux:modal.close>
                    <flux:button>{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </flux:modal>

    {{-- CREATE / EDIT MODAL --}}
    <flux:modal wire:model.self="showFormModal" title="{{ $editing ? __('Edit Cookbook') : __('Create Cookbook') }}">
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" label="{{ __('Title') }}"/>
            <flux:input wire:model="subtitle" label="{{ __('Subtitle') }}"/>

            <flux:radio.group wire:model="visibility" label="{{ __('Visibility') }}">
                <flux:radio value="community" label="{{ __('Community') }}"
                            description="{{ __('Editable by everyone in the community.') }}"/>
                <flux:radio value="public" label="{{ __('Public') }}"
                            description="{{ __('Viewable by everyone, editable only by you.') }}"/>
                <flux:radio value="private" label="{{ __('Private') }}"
                            description="{{ __('Only visible and editable by you.') }}"/>
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
