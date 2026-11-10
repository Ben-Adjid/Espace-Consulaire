<?php

namespace App\Policies;

/** Autorisations pour TimeSlot (permission d'écriture : manage_content). */
class TimeSlotPolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_content';
    }
}