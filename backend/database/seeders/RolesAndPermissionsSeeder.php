<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions.
 *
 *  super_admin      : tout (via Gate::before) + gestion des consulats
 *  consulate_admin  : contenus, rendez-vous, messages, utilisateurs et fiche de SON consulat
 *  staff            : rendez-vous + messages, lecture seule des contenus, aucune suppression
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'manage_content',       // services, actualités, FAQ, pages, créneaux
        'view_content',
        'manage_appointments',
        'manage_messages',
        'manage_users',
        'edit_consulate',
        'delete_records',
        'view_dashboard',
        'manage_receipts',      // encaissement au guichet (reçus)
        'decide_requests',      // décisions sur les pré-demandes : examen, prise en charge, cas particuliers
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web')->syncPermissions(self::PERMISSIONS);

        Role::findOrCreate(User::ROLE_CONSULATE_ADMIN, 'web')->syncPermissions(self::PERMISSIONS);

        Role::findOrCreate(User::ROLE_STAFF, 'web')->syncPermissions([
            'view_content', 'manage_appointments', 'manage_messages', 'view_dashboard', 'manage_receipts', 'decide_requests',
        ]);
        // Secrétariat : rendez-vous et messagerie uniquement. Ni encaissement, ni décision sur un dossier.
        Role::findOrCreate(User::ROLE_SECRETARIAT, 'web')->syncPermissions([
            'view_content', 'manage_appointments', 'manage_messages', 'view_dashboard',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
