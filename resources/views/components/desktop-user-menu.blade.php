<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        {{ $attributes->only('name') }}
        :avatar="auth()->user()->profileImageUrl()"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
        class="cursor-pointer"
    />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :src="auth()->user()->profileImageUrl()"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('cookbooks.index')" icon="book-open" wire:navigate>
                {{ __('Cookbooks') }}
            </flux:menu.item>
            <flux:menu.item :href="route('tags.index')" icon="tag" wire:navigate>
                {{ __('Tags') }}
            </flux:menu.item>
            @if (auth()->user()->isAdmin())
                <flux:menu.item :href="route('users.index')" icon="users" wire:navigate>
                    {{ __('Users') }}
                </flux:menu.item>
            @endif
            <flux:menu.separator />
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
