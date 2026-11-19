<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Registre des adhérents : lecture réservée au bureau en exercice, sans aucun contact. */
class AssociationMembersRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_sitting_board_members_can_read_the_registry_without_contacts(): void
    {
        $c = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $association = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'AETT', 'is_public' => true]);
        $make = fn (string $email) => Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => $email, 'email' => $email, 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $president = $make('president@example.test');
        $ordinary = $make('simple@example.test');
        AssociationOfficer::create(['association_id' => $association->id, 'role' => 'president', 'full_name' => 'Président', 'citizen_id' => $president->id, 'mandate_start' => now()->subMonth()]);
        AssociationMember::create(['association_id' => $association->id, 'citizen_id' => $ordinary->id, 'full_name' => 'Adhérent Un', 'status' => 'active', 'category' => 'student', 'joined_at' => now()->subYear()]);

        Sanctum::actingAs($president);
        $json = $this->getJson('/api/v1/tchad-togo/citizen/association/members')->assertOk()->json();
        $this->assertSame('Adhérent Un', $json['data'][0]['full_name']);
        $this->assertStringNotContainsString('@example.test', json_encode($json));

        Sanctum::actingAs($ordinary);
        $this->getJson('/api/v1/tchad-togo/citizen/association/members')->assertForbidden();
    }
}
