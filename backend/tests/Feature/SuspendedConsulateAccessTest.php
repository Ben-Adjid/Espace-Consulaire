<?php

namespace Tests\Feature;

use App\Models\Consulate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Consulat suspendu : le personnel voit un message clair, et tout le périmètre du consulat (site, association) est fermé. */
class SuspendedConsulateAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_CONSULATE_ADMIN, 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function consulate(array $overrides): Consulate
    {
        return Consulate::create(array_merge([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Consulat du Tchad au Sénégal'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ], $overrides));
    }

    private function staffOf(Consulate $c, string $role): User
    {
        $u = User::factory()->create(['is_active' => true, 'consulate_id' => $c->id]);
        $u->assignRole($role);

        return $u;
    }

    public function test_suspended_consul_gets_a_clear_message(): void
    {
        $c = $this->consulate(['is_active' => false, 'suspended_for_nonpayment' => true]);

        $this->actingAs($this->staffOf($c, User::ROLE_CONSULATE_ADMIN))->get('/admin')
            ->assertForbidden()
            ->assertSee('Le consulat Consulat du Tchad au Sénégal est suspendu')
            ->assertSee('Dès le paiement enregistré');
    }

    public function test_manually_deactivated_consulate_gets_its_own_message(): void
    {
        $c = $this->consulate(['is_active' => false, 'suspended_for_nonpayment' => false]);

        $this->actingAs($this->staffOf($c, User::ROLE_CONSULATE_ADMIN))->get('/admin')
            ->assertForbidden()
            ->assertSee('est désactivé')
            ->assertDontSee('suspendu');
    }

    public function test_suspension_closes_public_site_and_association(): void
    {
        $this->consulate(['is_active' => false, 'suspended_for_nonpayment' => true]);

        $this->getJson('/api/v1/tchad-senegal/services')->assertNotFound();
        $this->getJson('/api/v1/tchad-senegal/association')->assertNotFound();
    }
}
