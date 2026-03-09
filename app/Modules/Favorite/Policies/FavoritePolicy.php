<?php

namespace App\Modules\Favorite\Policies;

use App\Models\User;
use App\Modules\Favorite\Models\Favorite;
use Illuminate\Auth\Access\HandlesAuthorization;

class FavoritePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Favorite $favorite): bool
    {
        return $user->id === $favorite->user_id || $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Favorite $favorite): bool
    {
        return $user->id === $favorite->user_id || $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Favorite $favorite): bool
    {
        return $user->id === $favorite->user_id || $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Favorite $favorite): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Favorite $favorite): bool
    {
        return $user->isAdmin();
    }
}


