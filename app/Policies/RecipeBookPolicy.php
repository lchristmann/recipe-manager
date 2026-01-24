<?php

namespace App\Policies;

use App\Models\RecipeBook;
use App\Models\User;

class RecipeBookPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecipeBook $recipeBook): bool
    {
        return $recipeBook->user_id === $user->id
            || $recipeBook->community
            || !$recipeBook->private;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecipeBook $recipeBook): bool
    {
        return $user->isAdmin() || $recipeBook->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecipeBook $recipeBook): bool
    {
        return $user->isAdmin() || $recipeBook->user_id === $user->id;
    }
}
