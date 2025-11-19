<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Solo admins pueden ver la lista de negocios (pero no hay index en API)
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Business $business): bool
    {
        // Admin puede ver cualquier negocio, owner solo puede ver su propio negocio
        return $user->isAdmin() || $user->id === $business->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo owners pueden crear negocios (y solo uno por usuario)
        // Si ya tiene un negocio, no puede crear otro
        return $user->isOwner() && !$user->business;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Business $business): bool
    {
        // Admin puede actualizar cualquier negocio, owner solo puede actualizar su propio negocio
        return $user->isAdmin() || $user->id === $business->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Business $business): bool
    {
        // Solo admins pueden eliminar negocios (pero no hay destroy en API)
        return $user->isAdmin();
    }
}
