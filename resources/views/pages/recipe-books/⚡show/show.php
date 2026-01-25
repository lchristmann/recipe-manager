<?php

use App\Models\RecipeBook;
use Livewire\Component;

new class extends Component
{
    public RecipeBook $recipeBook;

    public function mount(RecipeBook $cookbook): void
    {
        $this->recipeBook = $cookbook;
    }
};
