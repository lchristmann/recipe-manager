<section class="w-full">
    @include('partials.users-heading')

    <flux:input icon="magnifying-glass"
        wire:model.live.debounce.250ms="search"
        placeholder="{{ __('Search users...') }}"
        class="w-full sm:w-64 mb-4"
    />

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column>
                <flux:icon.book-open class="size-4 -ml-0.5"/>
            </flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row wire:key="user-{{ $user->id }}">
                    <!-- Name, Email and Role -->
                    <flux:table.cell class="flex items-center gap-3">
                        <flux:avatar size="xs" :name="$user->name" />
                        {{ $user->name }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($user->isAdmin())
                            <flux:badge variant="solid" color="zinc" size="sm" inset="top bottom">{{ __('Admin') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm" inset="top bottom">{{ __('User') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->cookbooks_count }}</flux:table.cell>
                    <flux:table.cell>
                        <!-- Desktop buttons -->
                        <div class="hidden sm:flex justify-end gap-2">
                            @unless($user->id === auth()->id())
                                <flux:button icon="pencil" size="sm" wire:click="openEditModal({{ $user->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button icon="trash" size="sm" wire:click="openDeleteModal({{ $user->id }})">
                                    {{ __('Delete') }}
                                </flux:button>
                            @endunless
                        </div>

                        <!-- Mobile dropdown -->
                        <div class="sm:hidden">
                            @unless($user->id === auth()->id())
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil" wire:click="openEditModal({{ $user->id }})">
                                                {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="trash" wire:click="openDeleteModal({{ $user->id }})">
                                                {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endunless
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Create / Edit Modal -->
    <flux:modal wire:model.self="showFormModal" title="{{ $editing ? __('Edit User') : __('Create User') }}" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="name" label="{{ __('Name') }}" />
            <flux:input wire:model="email" label="{{ __('Email') }}" type="email" />
            <flux:input wire:model="password" label="{{ __('Password') }}" type="password" :placeholder="$editing ? '••••••••' : ''" />
            <flux:checkbox wire:model="is_admin" label="{{ __('Admin') }}"
                :disabled="$editing && $editing->id === auth()->id()"
            />

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Modal -->
    <flux:modal wire:model.self="showDeleteModal" title="{{ __('Confirm Deletion') }}" class="md:w-96">
        <div class="space-y-6">
            <p>{{ __('Are you sure you want to delete :name?', ['name' => $deleting->name ?? '']) }}</p>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
