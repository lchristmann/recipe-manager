@php
    use App\Models\RecipeBook;
    use App\Models\User;

    $communityBooks = RecipeBook::query()
        ->where('community', true)
        ->orderBy('position')
        ->get();

    $users = User::query()
        ->orderBy('name')
        ->get()
        ->map(function (User $user) {
            $booksQuery = RecipeBook::query()
                ->where('community', false)
                ->where('user_id', $user->id)
                ->orderBy('position');

            if ($user->id !== auth()->id()) {
                $booksQuery->where('private', false);
            }

            $user->sidebarBooks = $booksQuery->get();

            return $user;
        })
        ->filter(fn (User $user) => $user->sidebarBooks->isNotEmpty());
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('home') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                {{-- -------------------- COMMUNITY -------------------- --}}
                @if ($communityBooks->isNotEmpty())
                    <flux:sidebar.group :heading="__('Community')" class="grid">
                        @foreach ($communityBooks as $book)
                            <flux:sidebar.item
                                icon="book-open"
                                :href="route('cookbooks.show', ['cookbook' => $book->id])"
                                :current="request()->routeIs('cookbooks.show') && request()->route('cookbook')?->id === $book->id"
                                wire:navigate
                            >
                                {{ $book->title }}
                            </flux:sidebar.item>
                        @endforeach
                    </flux:sidebar.group>
                @endif

                {{-- -------------------- USERS -------------------- --}}
                @foreach ($users as $user)
                    <flux:sidebar.group :heading="$user->name" class="grid">
                        @foreach ($user->sidebarBooks as $book)
                            <flux:sidebar.item
                                icon="book-open"
                                :href="route('cookbooks.show', ['cookbook' => $book->id])"
                                :current="request()->routeIs('cookbooks.show') && request()->route('cookbook')?->id === $book->id"
                                wire:navigate
                            >
                                {{ $book->title }}
                            </flux:sidebar.item>
                        @endforeach
                    </flux:sidebar.group>
                @endforeach
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>


        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('cookbooks.index')" icon="book-open" wire:navigate>
                            {{ __('Cookbooks') }}
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
                    </flux:menu.radio.group>

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
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
