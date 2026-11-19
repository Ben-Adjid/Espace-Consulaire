<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Gouvernance : c'est le consulat qui ouvre et organise les élections ; l'association ne le fait jamais. */
class ElectionGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->consulate = Consulate::create([
            'slug' => 'gov-test', 'ref_prefix' => 'GOV', 'name' => ['fr' => 'Gov'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->association = Association::withoutGlobalScopes()->create(['consulate_id' => $this->consulate->id, 'name' => 'GOV', 'is_public' => true]);
    }

    public function test_consulate_admin_can_run_elections(): void
    {
        $consul = User::factory()->create(['is_active' => true, 'consulate_id' => $this->consulate->id]);
        $consul->assignRole(User::ROLE_CONSULATE_ADMIN);

        $this->assertTrue($consul->can('update', $this->association));
    }

    public function test_agent_cannot_run_elections(): void
    {
        $agent = User::factory()->create(['is_active' => true, 'consulate_id' => $this->consulate->id]);
        $agent->assignRole(User::ROLE_STAFF);

        $this->assertFalse($agent->can('update', $this->association));
    }

    public function test_board_president_cannot_open_or_create_an_election(): void
    {
        $president = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Président', 'email' => 'pres@example.test', 'phone' => '+000 00 00 00 01',
            'password' => 'mot-de-passe-test', 'nationality' => 'Test', 'residence_country' => 'Test',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'Président', 'citizen_id' => $president->id, 'mandate_start' => now()->subMonth(), 'mandate_end' => now()->addYear()]);

        Sanctum::actingAs($president);
        $this->postJson('/api/v1/gov-test/citizen/association-elections', ['year' => 2027])->assertStatus(405);
    }
}
