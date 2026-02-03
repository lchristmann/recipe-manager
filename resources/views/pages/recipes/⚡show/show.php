<?php

use App\Models\Recipe;
use Livewire\Component;

new class extends Component
{
    public Recipe $recipe;

    public function mount(Recipe $recipe): void
    {
        $this->recipe = $recipe->load([
            'cookbook',
            'links',
            'photoImages',
            'recipeImages',
            'tags',
        ]);
    }
};
