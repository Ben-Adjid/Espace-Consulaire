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
            'view_content', 'manage_appointments', 'manage_messages', 'view_dashboard',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
