<?php

use App\Models\Cookbook;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'cookbook')]
    public ?int $cookbookId = null;

    public ?Cookbook $cookbook = null;

    public function mount(): void
    {
        if ($this->cookbookId) {
            $this->cookbook = Cookbook::findOrFail($this->cookbookId);

            $this->authorize('update', $this->cookbook);
        }
    }
};
