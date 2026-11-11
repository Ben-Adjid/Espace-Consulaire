<?php

namespace App\Policies;

/** Autorisations pour ContactMessage (permission d'écriture : manage_messages). */
class ContactMessagePolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_messages';
    }

    protected function viewPermission(): string
    {
        return 'manage_messages';
    }
}