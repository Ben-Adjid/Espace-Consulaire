<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Filament;
use Tests\TestCase;

/** Secrétariat : rendez-vous et messagerie, sans encaissement ni décision sur les dossiers. */
class SecretariatRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretariat_can_manage_appointments_but_not_receipts_or_decisions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $secretary = User::factory()->create(['is_active' => true, 'consulate_id' => null]);
        $secretary->assignRole(User::ROLE_SECRETARIAT);

        $this->assertTrue($secretary->can('manage_appointments'));
        $this->assertTrue($secretary->can('manage_messages'));
        $this->assertFalse($secretary->can('manage_receipts'));
        $this->assertFalse($secretary->can('decide_requests'));
    }

    public function test_staff_keeps_receipts_and_decisions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->assignRole(User::ROLE_STAFF);

        $this->assertTrue($staff->can('manage_receipts'));
        $this->assertTrue($staff->can('decide_requests'));
    }
}
