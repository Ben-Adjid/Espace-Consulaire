<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestion des utilisateurs : un consulate_admin ne gère que les comptes de son
 * consulat et jamais un super_admin. Le super_admin passe par Gate::before.
 */
class UserPolicy
{
    private function manages(User $user, User $target): bool
    {
        return $user->can('manage_users')
            && $user->consulate_id !== null
            && (int) $user->consulate_id === (int) $target->consulate_id
            && ! $target->isSuperAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function view(User $user, User $target): bool
    {
        return $this->manages($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function update(User $user, User $target): bool
    {
        return $this->manages($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->manages($user, $target) && $user->id !== $target->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('manage_users');
    }
}
