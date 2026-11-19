<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** L'abonnement du consulat couvre tout son personnel : un agent d'un consulat suspendu est bloqué, comme le consul. */
class StaffCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_of_a_suspended_consulate_is_blocked_like_the_consul(): void
    {
        foreach ([User::ROLE_CONSULATE_ADMIN, 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => false,
            'suspended_for_nonpayment' => true,
        ]);

        $agent = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $agent->assignRole('staff');

        $this->actingAs($agent)->get('/admin')->assertForbidden()->assertSee('est suspendu');
    }
}
