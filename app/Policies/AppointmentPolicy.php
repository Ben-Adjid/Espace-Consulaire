<?php

namespace App\Policies;

/** Autorisations pour Appointment (permission d'écriture : manage_appointments). */
class AppointmentPolicy extends ConsulateOwnedPolicy
{
    protected function managePermission(): string
    {
        return 'manage_appointments';
    }

    protected function viewPermission(): string
    {
        return 'manage_appointments';
    }
}