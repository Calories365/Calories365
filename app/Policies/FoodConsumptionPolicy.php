<?php

namespace App\Policies;

use App\Models\FoodConsumption;
use App\Models\User;

class FoodConsumptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, FoodConsumption $foodConsumption): bool
    {
        return $user->id === $foodConsumption->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FoodConsumption $foodConsumption): bool
    {
        return $user->id === $foodConsumption->user_id;
    }

    public function delete(User $user, FoodConsumption $foodConsumption): bool
    {
        return $user->id === $foodConsumption->user_id;
    }

    public function restore(User $user, FoodConsumption $foodConsumption): bool
    {
        return $user->id === $foodConsumption->user_id;
    }

    public function forceDelete(User $user, FoodConsumption $foodConsumption): bool
    {
        return $user->id === $foodConsumption->user_id;
    }
}
