<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\Cookbook;
use App\Models\User;

class RecipePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Recipe $recipe): bool
    {
        return $user->can('view', $recipe->cookbook);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Cookbook $cookbook): bool
    {
        return $cookbook->user_id === $user->id
            || $cookbook->community;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        $cookbook = $recipe->cookbook;

        return $cookbook->user_id === $user->id
            || $cookbook->community;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        $cookbook = $recipe->cookbook;

        return $cookbook->user_id === $user->id
            || $cookbook->community;
    }
}
