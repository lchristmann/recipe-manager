<?php

use App\Models\Cookbook;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[On('cookbooks-changed')]
    #[On('users-changed')]
    public function refreshSidebar(): void
    {
        // the event triggers a re-render of the component for a fresh sidebar
    }

    #[Computed]
    public function communityBooks()
    {
        return Cookbook::query()
            ->where('community', true)
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $booksQuery = Cookbook::query()
                    ->where('community', false)
                    ->where('user_id', $user->id)
                    ->orderBy('position');

                if ($user->id !== auth()->id()) {
                    $booksQuery->where('private', false);
                }

                $user->sidebarBooks = $booksQuery->get();

                return $user;
            })
            ->filter(fn(User $user) => $user->sidebarBooks->isNotEmpty());
    }

    #[Computed]
    public function currentCookbookId(): ?int
    {
        $route = request()->route();
        if (!$route) return null;

        return match ($route->getName()) {
            'cookbooks.show' => $route->parameter('cookbook')?->id,

            'recipes.show', 'recipes.edit' => $route->parameter('recipe')?->cookbook_id,

            'recipes.create' => request()->integer('cookbook'),

            default => null,
        };
    }
};
?>

<div class="contents">
    <flux:sidebar sticky collapsible="mobile"
                  class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('home') }}" wire:navigate/>
            <flux:sidebar.collapse class="lg:hidden"/>
        </flux:sidebar.header>

        <flux:sidebar.nav>
            {{-- -------------------- COMMUNITY -------------------- --}}
            @if ($this->communityBooks->isNotEmpty())
                <flux:sidebar.group :heading="__('Community')" class="grid">
                    @foreach ($this->communityBooks as $book)
                        <flux:sidebar.item
                            icon="book-open"
                            :href="route('cookbooks.show', ['cookbook' => $book->id])"
                            :current="$this->currentCookbookId === $book->id"
                            wire:key="cb-c-{{ $book->id }}"
                            wire:navigate
                        >
                            {{ $book->title }}
                        </flux:sidebar.item>
                    @endforeach
                </flux:sidebar.group>
            @endif

            {{-- -------------------- USERS -------------------- --}}
            @foreach ($this->users as $user)
                <flux:sidebar.group :heading="$user->name" class="grid">
                    @foreach ($user->sidebarBooks as $book)
                        <flux:sidebar.item
                            icon="book-open"
                            :href="route('cookbooks.show', ['cookbook' => $book->id])"
                            :current="$this->currentCookbookId === $book->id"
                            wire:key="cb-p-{{ $book->id }}"
                            wire:navigate
                        >
                            {{ $book->title }}
                        </flux:sidebar.item>
                    @endforeach
                </flux:sidebar.group>
            @endforeach
        </flux:sidebar.nav>

        <flux:spacer/>

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name"/>
    </flux:sidebar>
</div>
