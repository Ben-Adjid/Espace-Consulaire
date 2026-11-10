<?php

namespace App\Policies;

/** Autorisations pour Service (permission d'écriture : manage_content). */
class ServicePolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_content';
    }
}