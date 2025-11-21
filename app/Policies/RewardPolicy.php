<?php

namespace App\Policies;

use App\Models\Reward;
use App\Models\User;

class RewardPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() && $user->business !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reward $reward): bool
    {
        if (! $user->business) {
            return false;
        }
        
        // Verificar que el reward pertenezca al business
        return $reward->business_id === $user->business->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isOwner() && $user->business !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reward $reward): bool
    {
        if (! $user->business) {
            return false;
        }
        
        // Verificar que el reward pertenezca al business
        return $reward->business_id === $user->business->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reward $reward): bool
    {
        if (! $user->business) {
            return false;
        }
        
        // Verificar que el reward pertenezca al business
        return $reward->business_id === $user->business->id;
    }
}
