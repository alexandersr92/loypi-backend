<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // El owner puede ver la lista de customers de su negocio
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        // Solo puede ver customers de su propio negocio
        return $user->business && $user->business->id === $customer->business_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo owners con negocio pueden crear customers (aunque normalmente se registran ellos mismos)
        return $user->isOwner() && $user->business !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        // Solo puede actualizar customers de su propio negocio
        return $user->business && $user->business->id === $customer->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        // Solo puede eliminar customers de su propio negocio
        return $user->business && $user->business->id === $customer->business_id;
    }
}
