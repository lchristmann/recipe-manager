<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\RecipeBook;
use App\Models\User;

class RecipePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Recipe $recipe): bool
    {
        return $user->can('view', $recipe->recipeBook);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, RecipeBook $recipeBook): bool
    {
        return $recipeBook->user_id === $user->id
            || $recipeBook->community;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        $recipeBook = $recipe->recipeBook;

        return $recipeBook->user_id === $user->id
            || $recipeBook->community;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        $recipeBook = $recipe->recipeBook;

        return $recipeBook->user_id === $user->id
            || $recipeBook->community;
    }
}
