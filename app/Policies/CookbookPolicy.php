<?php

namespace App\Policies;

use App\Models\Cookbook;
use App\Models\User;

class CookbookPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cookbook $cookbook): bool
    {
        return $cookbook->community
            || !$cookbook->private
            || $cookbook->user_id === $user->id;
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
    public function update(User $user, Cookbook $cookbook): bool
    {
        return $user->isAdmin() || $cookbook->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cookbook $cookbook): bool
    {
        return $user->isAdmin() || $cookbook->user_id === $user->id;
    }
}
