<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // El owner puede ver la lista de staff de su negocio
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Staff $staff): bool
    {
        // Solo puede ver staff de su propio negocio
        return $user->business && $user->business->id === $staff->business_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo owners con negocio pueden crear staff
        return $user->isOwner() && $user->business !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Staff $staff): bool
    {
        // Solo puede actualizar staff de su propio negocio
        return $user->business && $user->business->id === $staff->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Staff $staff): bool
    {
        // Solo puede eliminar staff de su propio negocio
        return $user->business && $user->business->id === $staff->business_id;
    }
}
