<?php

namespace App\Policies;

use App\Models\FoodConsumption;
use App\Models\User;

class FoodConsumptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function index(User $user, FoodConsumption $foodConsumption)
    {
        return $user->id === $foodConsumption->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FoodConsumption $foodConsumption): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FoodConsumption $foodConsumption): bool
    {
        return $user->id === $foodConsumption->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FoodConsumption $foodConsumption): bool
    {
        return $user->id === $foodConsumption->user_id;
    }


    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FoodConsumption $foodConsumption): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FoodConsumption $foodConsumption): bool
    {
        //
    }
}
