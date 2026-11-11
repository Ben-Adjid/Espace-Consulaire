<?php

namespace App\Policies;

use App\Models\Consulate;
use App\Models\User;

/**
 * Consulats : création/suppression réservées au super_admin (Gate::before).
 * Un consulate_admin peut modifier uniquement SON consulat.
 */
class ConsulatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('edit_consulate');
    }

    public function view(User $user, Consulate $consulate): bool
    {
        return $user->can('edit_consulate') && (int) $user->consulate_id === (int) $consulate->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Consulate $consulate): bool
    {
        return $this->view($user, $consulate);
    }

    public function delete(User $user, Consulate $consulate): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
