<?php

use App\Models\Cookbook;
use Livewire\Component;

new class extends Component
{
    public Cookbook $cookbook;

    public function mount(Cookbook $cookbook): void
    {
        $this->cookbook = $cookbook;
    }
};
