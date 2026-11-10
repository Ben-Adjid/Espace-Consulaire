<?php

namespace App\Policies;

/** Autorisations pour Faq (permission d'écriture : manage_content). */
class FaqPolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_content';
    }
}