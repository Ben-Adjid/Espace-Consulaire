<?php

namespace Tests\Feature\Api;

use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\Dependent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Espace famille : personnes à charge gérées depuis un seul compte, cloisonnées par citoyen. */
class FamilySpaceTest extends TestCase
{
    use RefreshDatabase;

    private Citizen $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $c = Consulate::create([
            'slug' => 'tchad-senegal', 'ref_prefix' => 'TCH-SN', 'name' => ['fr' => 'Tchad'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Sénégal'],
            'covered_countries' => ['Sénégal'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->parent = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Parent', 'email' => 'parent@example.test', 'phone' => '+235 66 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    public function test_parent_adds_a_child_and_sees_the_family_list(): void
    {
        Sanctum::actingAs($this->parent);

        $this->postJson('/api/v1/tchad-senegal/citizen/dependents', ['full_name' => 'Enfant Un', 'relation' => 'child', 'birth_date' => '2015-03-01'])
            ->assertCreated()->assertJsonPath('data.relation_label', 'relation_child');

        $this->getJson('/api/v1/tchad-senegal/citizen/dependents')->assertOk()->assertJsonPath('data.0.full_name', 'Enfant Un');
    }

    public function test_relation_must_be_known_and_birth_date_in_the_past(): void
    {
        Sanctum::actingAs($this->parent);

        $this->postJson('/api/v1/tchad-senegal/citizen/dependents', ['full_name' => 'X Y', 'relation' => 'ami'])->assertUnprocessable();
        $this->postJson('/api/v1/tchad-senegal/citizen/dependents', ['full_name' => 'X Y', 'relation' => 'child', 'birth_date' => now()->addYear()->toDateString()])
            ->assertUnprocessable();
    }

    public function test_another_citizen_cannot_remove_my_dependent(): void
    {
        $dep = Dependent::create(['citizen_id' => $this->parent->id, 'full_name' => 'Enfant', 'relation' => 'child']);
        $other = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->parent->consulate_id, 'full_name' => 'Autre', 'email' => 'autre@example.test', 'phone' => '+235 66 00 00 01',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Sénégal',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($other);
        $this->deleteJson("/api/v1/tchad-senegal/citizen/dependents/{$dep->id}")->assertNotFound();
        $this->assertNotNull(Dependent::find($dep->id));
    }

    public function test_family_space_requires_an_account(): void
    {
        $this->getJson('/api/v1/tchad-senegal/citizen/dependents')->assertUnauthorized();
    }
}
