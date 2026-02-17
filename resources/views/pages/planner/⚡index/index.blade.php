@php
    use Carbon\Carbon;
@endphp

<section class="w-full max-w-3xl space-y-6">

    {{-- Week navigation --}}
    <div class="flex items-center justify-between">
        <flux:button icon="chevron-left" wire:click="previousWeek" class="cursor-pointer"/>

        <flux:heading size="lg">
            {{ $weekStart->translatedFormat('M j') }} -
            {{ $weekStart->addDays(6)->translatedFormat('M j') }}
        </flux:heading>

        <div class="flex gap-2">
            <flux:button icon="chevron-double-down" wire:click="goToCurrentWeek" class="cursor-pointer"/>
            <flux:button icon="chevron-right" wire:click="nextWeek" class="cursor-pointer"/>
        </div>
    </div>

    {{-- Days --}}
    @foreach($this->days as $date)
        <div class="space-y-3" wire:key="planner-day-{{ $date }}">
            <flux:heading size="md">{{ Carbon::parse($date)->translatedFormat('M j') }}</flux:heading>

            {{-- Existing planner rows --}}
            @foreach($plans[$date] ?? [] as $plan)
                <livewire:planner.row :key="$plan['uuid']"
                    :plan="$plan"
                    :date="$date"
                />
            @endforeach

            {{-- Create row --}}
            <livewire:planner.row :key="'new-'.$date.'-'.$createRowVersion"
                :plan="[
                    'uuid' => (string) str()->uuid(),
                    'id' => null,
                    'recipe_id' => null,
                    'recipe_name' => '',
                    'mode' => 'create'
                ]"
                :date="$date"
            />

        </div>
    @endforeach

</section>
