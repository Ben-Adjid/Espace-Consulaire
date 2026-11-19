<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Par défaut : uniquement les rôles et permissions (aucune donnée fictive).
     * Les consulats et comptes de démonstration ne sont créés que si SEED_DEMO=true
     * (environnement de développement), jamais sur la base réelle.
     */
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, FeatureBacklogSeeder::class]);

        if (filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOL)) {
            $this->call([
                ConsulateSeeder::class,   // Consulats fictifs (contenus, créneaux, utilisateurs)
                SuperAdminSeeder::class,
            ]);
        }
    }
}
