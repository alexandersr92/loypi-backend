<?php

namespace App\Policies;

use App\Models\CustomField;
use App\Models\User;

class CustomFieldPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // El owner puede ver la lista de custom fields de su negocio
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomField $customField): bool
    {
        // Solo puede ver custom fields de su propio negocio
        return $user->business && $user->business->id === $customField->business_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Owners y admins con negocio pueden crear custom fields
        return ($user->isOwner() || $user->isAdmin()) && $user->business !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomField $customField): bool
    {
        // Owners y admins pueden actualizar custom fields de su propio negocio
        return ($user->isOwner() || $user->isAdmin()) 
            && $user->business 
            && $user->business->id === $customField->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomField $customField): bool
    {
        // Owners y admins pueden eliminar custom fields de su propio negocio
        // Y solo si no tiene valores de customers
        return ($user->isOwner() || $user->isAdmin())
            && $user->business 
            && $user->business->id === $customField->business_id
            && !$customField->hasCustomerValues();
    }
}
