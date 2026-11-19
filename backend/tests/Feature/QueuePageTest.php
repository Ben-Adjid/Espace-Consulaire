<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Page « File du jour » : accessible au personnel rattaché à un consulat actif. */
class QueuePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_of_an_active_consulate_can_open_the_queue_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $staff = User::create(['name' => 'Agent', 'email' => 'agent@example.test', 'password' => 'mot-de-passe-long', 'consulate_id' => $c->id, 'is_active' => true]);
        $staff->assignRole('staff');

        $this->actingAs($staff)->withSession(['two_factor_passed' => $staff->id])
            ->get('/admin/file-du-jour')->assertOk();
    }
}
