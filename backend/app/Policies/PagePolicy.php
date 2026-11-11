<?php

namespace App\Policies;

/** Autorisations pour Page (permission d'écriture : manage_content). */
class PagePolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_content';
    }
}