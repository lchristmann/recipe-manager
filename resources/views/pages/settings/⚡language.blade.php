<?php

use Livewire\Component;

new class extends Component {

    public string $locale;

    public function mount(): void
    {
        $this->locale = auth()->user()?->locale ?? app()->getLocale();
    }

    public function updatedLocale($value): void
    {
        auth()->user()->update(['locale' => $value]);

        // refresh the page: https://stackoverflow.com/q/64874410/20594090
        $this->redirect(request()->header('Referer'), navigate: true);
    }

}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Language Settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Language')"
                              :subheading="__('Choose your preferred application language')">
        <flux:select variant="listbox" wire:model.live="locale" placeholder="{{ __('Select language') }}">
            <flux:select.option value="en">English</flux:select.option>
            <flux:select.option value="de">Deutsch</flux:select.option>
        </flux:select>
    </x-pages::settings.layout>
</section>
