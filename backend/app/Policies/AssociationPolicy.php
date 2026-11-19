<?php

namespace App\Policies;

/** Association des étudiants : lecture pour le personnel, gestion par l’administrateur du consulat. */
class AssociationPolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_content';
    }
}
