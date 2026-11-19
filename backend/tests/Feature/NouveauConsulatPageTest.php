<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\NouveauConsulat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** La page de création de consulat est réservée au super admin et se rend correctement. */
class NouveauConsulatPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_CONSULATE_ADMIN, 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_only_super_admin_can_access_the_wizard(): void
    {
        $this->actingAs($this->user(User::ROLE_SUPER_ADMIN));
        $this->assertTrue(NouveauConsulat::canAccess());

        $this->actingAs($this->user('staff'));
        $this->assertFalse(NouveauConsulat::canAccess());
    }

    public function test_wizard_page_renders_for_super_admin(): void
    {
        $this->actingAs($this->user(User::ROLE_SUPER_ADMIN));

        $this->get('/admin/nouveau-consulat')
            ->assertOk()
            ->assertSee('Identité')
            ->assertSee('Créneaux');
    }
}
