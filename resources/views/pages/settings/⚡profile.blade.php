<?php

use App\Concerns\ProfileValidationRules;
use App\Constants\StorageConstants;
use App\Models\User;
use App\Support\Image\UserImageProcessor;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component {
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $email = '';

    public bool $removeExistingPhoto = false;

    public ?TemporaryUploadedFile $photo = null;

    protected function rules(): array
    {
        return array_merge(
            $this->profileRules(Auth::id()),
            [
                'photo' => ['nullable', 'image', 'max:2048'],
            ]
        );
    }

    protected function messages(): array
    {
        return [
            'photo.image' => __('The profile photo must be an image file.'),
            'photo.max' => __('The profile photo may not be larger than 2 MB.'),
        ];
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function removeImage(): void
    {
        $this->photo->delete();

        $this->photo = null;
    }

    public function removeExistingImage(): void
    {
        $this->removeExistingPhoto = true;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate();

        $user->fill($validated);

        $nameChanged = $user->isDirty('name');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($this->photo) {
            // If new photo uploaded, delete old one
            if ($user->image_path) {
                Storage::deleteDirectory(StorageConstants::USER_IMAGES . '/' . $user->image_path);
            }

            $folder = UserImageProcessor::process($this->photo);

            $user->update(['image_path' => $folder]);

            $this->reset('photo');
            $this->removeExistingPhoto = false;

            $imageChanged = true;
        } elseif ($this->removeExistingPhoto && $user->image_path) {
            // Delete old photo, no replacement
            Storage::deleteDirectory(StorageConstants::USER_IMAGES . '/' . $user->image_path);

            $user->update(['image_path' => null]);

            $this->removeExistingPhoto = false;

            $imageChanged = true;
        }

        $this->dispatch('profile-updated', name: $user->name);

        if ($nameChanged) {
            $this->dispatch('users-changed');
        }

        if (isset($imageChanged)) {
            $this->redirect(request()->header('Referer'));
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="space-y-4">

                <flux:file-upload wire:model="photo" :label="__('Profile Photo')">
                    <flux:file-upload.dropzone :heading="__('Drop image or click to browse')" :text="__('JPG, PNG, GIF, WEBP up to 2MB')" with-progress inline/>
                </flux:file-upload>

                <div class="mt-3 flex flex-col gap-2">
                    @if ($photo)
                        {{-- New image upload --}}
                        <flux:file-item :heading="$photo->getClientOriginalName()" :image="$photo->isPreviewable() ? $photo->temporaryUrl() : null" :size="$photo->getSize()">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeImage" aria-label="{{ __('Remove file') }}" class="cursor-pointer"/>
                            </x-slot>
                        </flux:file-item>
                    @elseif(auth()->user()->hasImage() && !$removeExistingPhoto)
                        {{-- Existing stored image --}}
                        @php
                            $folder = auth()->user()->image_path;
                            $originalPath = StorageConstants::USER_IMAGES . '/' . $folder . '/original.webp';
                            $size = Storage::exists($originalPath) ? Storage::size($originalPath) : null;
                        @endphp

                        <flux:file-item :heading="__('Current profile photo')" :image="auth()->user()->profileImageUrl()" :size="$size">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeExistingImage" aria-label="{{ __('Remove image') }}" class="cursor-pointer"/>
                            </x-slot>
                        </flux:file-item>
                    @endif
                </div>

            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name"/>

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full cursor-pointer" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
