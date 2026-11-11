<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de base pour les contenus rattachés à un consulat.
 *
 * Deux barrières cumulées :
 *  1. la permission Spatie (selon le rôle) ;
 *  2. l'appartenance de l'enregistrement au consulat de l'utilisateur.
 *
 * Le super_admin passe par Gate::before (voir AppServiceProvider).
 */
abstract class ConsulateOwnedPolicy
{
    /** Permission d'écriture (créer / modifier). */
    abstract protected function managePermission(): string;

    /** Permission de lecture (par défaut : lecture des contenus). */
    protected function viewPermission(): string
    {
        return 'view_content';
    }

    protected function sameConsulate(User $user, Model $model): bool
    {
        return $user->consulate_id !== null && (int) $user->consulate_id === (int) $model->consulate_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->viewPermission()) || $user->can($this->managePermission());
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user) && $this->sameConsulate($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can($this->managePermission());
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->managePermission()) && $this->sameConsulate($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('delete_records') && $this->sameConsulate($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_records');
    }
}
