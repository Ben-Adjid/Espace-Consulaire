<?php

namespace App\Policies;

/** Autorisations pour News (permission d'écriture : manage_content). */
class NewsPolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_content';
    }
}