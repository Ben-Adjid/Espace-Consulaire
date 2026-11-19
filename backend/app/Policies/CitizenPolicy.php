<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\User;

/** Gestion des comptes citoyens : réservée aux administrateurs, limitée à leur consulat. */
class CitizenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function view(User $user, Citizen $citizen): bool
    {
        return $this->viewAny($user) && $this->sameConsulate($user, $citizen);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Citizen $citizen): bool
    {
        return $this->viewAny($user) && $this->sameConsulate($user, $citizen);
    }

    public function delete(User $user, Citizen $citizen): bool
    {
        return false;
    }

    private function sameConsulate(User $user, Citizen $citizen): bool
    {
        return $user->isSuperAdmin() || (int) $user->consulate_id === (int) $citizen->consulate_id;
    }
}
