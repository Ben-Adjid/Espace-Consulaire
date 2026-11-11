<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Compte super_admin de démonstration (mot de passe : voir ConsulateSeeder::demoPassword()). */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'superadmin@demo.test'],
            [
                'name' => 'Super Admin',
                'password' => ConsulateSeeder::demoPassword(),
                'is_active' => true,
                'consulate_id' => null,
            ],
        );

        $user->syncRoles([User::ROLE_SUPER_ADMIN]);
    }
}
