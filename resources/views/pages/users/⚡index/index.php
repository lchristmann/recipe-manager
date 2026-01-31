<?php

use App\Models\Cookbook;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    // Modal state
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    // Currently editing / deleting user
    public ?User $editing = null;
    public ?User $deleting = null;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $is_admin = false;

    // Search input
    public string $search = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editing),
            ],
            'password' => [$this->editing ? 'nullable' : 'required', 'min:8'],
            'is_admin' => ['boolean'],
        ];
    }

    /** Reset form fields */
    protected function resetForm(): void
    {
        $this->reset([
            'editing',
            'name',
            'email',
            'password',
            'is_admin',
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // -------------------- queries --------------------

    // example in docs regarding pagination: https://livewire.laravel.com/docs/4.x/pagination#resetting-the-page
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->withCount('cookbooks')
            ->orderByDesc('id')
            ->when($this->search, fn (Builder $query) =>
                $query->whereRaw('LOWER(name) LIKE ? OR LOWER(email) LIKE ?', [
                    '%' . strtolower($this->search) . '%',
                    '%' . strtolower($this->search) . '%',
                ])
            )
            ->paginate(10);
    }

    // -------------------- create / update / delete --------------------

    public function save(): void
    {
        $isCreating = is_null($this->editing);

        $this->authorize($isCreating ? 'create' : 'update', $this->editing ?? User::class);

        $validated = $this->validate();

        $user = $this->editing ?? new User();

        // Hash password only if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->fill($validated);
        if ($isCreating) $user->email_verified_at = now();
        $user->save();

        if ($isCreating) $this->resetPage();

        $this->dispatch('users-changed');

        $this->resetForm();
        $this->showFormModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deleting);

        // Grab the user's community cookbooks count
        $communityBooksCount = Cookbook::query()
            ->where('community', true)
            ->where('user_id', $this->deleting->id)
            ->count();

        // Delete the user (personal cookbooks are deleted by cascade automatically)
        $this->deleting->delete();

        // Close gaps in community cookbooks positions if the user had any
        if ($communityBooksCount > 0) {
            Cookbook::query()
                ->where('community', true)
                ->orderBy('position')
                ->get()
                ->values()
                ->each(function (Cookbook $cookbook, int $index) {
                    if ($cookbook->position !== $index) {
                        $cookbook->update(['position' => $index]);
                    }
                });
        }

        $this->dispatch('users-changed');

        $this->deleting = null;
        $this->showDeleteModal = false;
    }

    // -------------------- modal helpers --------------------

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(User $user): void
    {
        $this->authorize('update', $user);

        $this->editing = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_admin = $user->is_admin;
        $this->password = '';

        $this->showFormModal = true;
    }

    public function openDeleteModal(User $user): void
    {
        $this->authorize('delete', $user);

        $this->deleting = $user;
        $this->showDeleteModal = true;
    }
};
