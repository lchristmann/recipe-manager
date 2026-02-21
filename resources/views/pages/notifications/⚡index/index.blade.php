<section class="w-full max-w-3xl space-y-6">
    @include('partials.notifications-heading')

    <div class="space-y-3.5">

        @forelse($this->notifications as $notification)
            @php
                $actor = $notification->actor;
                $comment = $notification->comment;
                $isUnread = !$notification->read;
            @endphp

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition">
                <div wire:click="openNotification({{ $notification->id }})" class="p-4 cursor-pointer">
                    <div class="flex gap-3">
                        {{-- Avatar --}}
                        <flux:avatar :src="$actor->profileImageUrl()" :initials="$actor->initials()" size="sm" class="shrink-0"/>

                        <div class="flex-1 min-w-0 space-y-2">

                            {{-- Header --}}
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                <flux:text inline :variant="$isUnread ? 'strong' : 'default'">
                                    {{ $actor->name }}
                                    {{ $notification->type === \App\Enums\NotificationType::REPLY ? __('replied') : __('commented') }}:
                                </flux:text>

                                <flux:text size="sm" :variant="$isUnread ? 'strong' : 'default'">{{ $notification->created_at->diffForHumans() }}</flux:text>
                            </div>

                            {{-- Body + Read/Unread Toggle --}}
                            <div class="flex items-center gap-4">
                                <flux:text class="flex-1 line-clamp-3 whitespace-pre-wrap" :variant="$isUnread ? 'strong' : 'default'">
                                    {{ Str::limit($comment->body, 320) }}
                                </flux:text>

                                <div class="shrink-0 flex items-center">
                                    <flux:button wire:click.stop="toggleRead({{ $notification->id }})" size="sm" variant="outline"
                                        icon="{{ $isUnread ? 'check' : 'rotate-ccw' }}" class="cursor-pointer"/>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700">
                <flux:text variant="subtle">{{ __('Your inbox is empty.') }}</flux:text>
            </div>
        @endforelse

    </div>

    {{-- Delete Modal --}}
    <flux:modal wire:model.self="showClearModal" title="{{ __('Clear inbox') }}" class="md:w-96">
        <div class="space-y-6">
            <flux:text>{{ __('Are you sure you want to delete all notifications? This cannot be undone.') }}</flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="clearAll" class="cursor-pointer">
                    {{ __('Delete all') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
