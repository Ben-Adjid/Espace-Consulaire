<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Un consul peut ouvrir la liste et la création de l'association de son consulat. */
class AssociationCreateByConsulTest extends TestCase
{
    use RefreshDatabase;

    public function test_consul_can_open_association_list_and_create_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $consul = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $consul->assignRole(User::ROLE_CONSULATE_ADMIN);
        // Le consul gère le bureau : la double authentification est obligatoire, il faut donc un compte qui l'a activée.
        $consul->forceFill(['two_factor_secret' => 'secret-test', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($consul);
        session(['two_factor_passed' => $consul->getKey()]);

        $this->get('/admin/associations')->assertOk();
        $this->get('/admin/associations/create')->assertOk();
    }
}
