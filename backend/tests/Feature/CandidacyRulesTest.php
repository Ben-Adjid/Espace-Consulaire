<?php

namespace Tests\Feature;

use App\Actions\CreateOnlineElection;
use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\AssociationMember;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Règles de candidature : déclaration sur l'honneur, et postes ouverts par une élection partielle. */
class CandidacyRulesTest extends TestCase
{
    use RefreshDatabase;

    private AssociationElection $election;

    private Citizen $citizen;

    protected function setUp(): void
    {
        parent::setUp();
        $c = Consulate::create([
            'slug' => 'cand-test', 'ref_prefix' => 'CAN', 'name' => ['fr' => 'Cand'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $association = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'CAN', 'is_public' => true]);
        $this->citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => 'Candidat', 'email' => 'cand@example.test', 'phone' => '+000 00 00 00 02',
            'password' => 'mot-de-passe-test', 'nationality' => 'Test', 'residence_country' => 'Test',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        AssociationMember::create(['association_id' => $association->id, 'citizen_id' => $this->citizen->id, 'full_name' => 'Candidat', 'email' => 'cand@example.test', 'status' => 'active', 'category' => 'student', 'joined_at' => now()->subYears(2)]);
        // Élection en phase de candidature : le vote ouvre dans un jour.
        $this->election = app(CreateOnlineElection::class)($association, 2028, now()->addDay()->toDateTimeString(), now()->addDays(2)->toDateTimeString(), null, 72);
        Sanctum::actingAs($this->citizen);
    }

    public function test_candidacy_requires_the_no_family_link_declaration(): void
    {
        $this->postJson("/api/v1/cand-test/citizen/association-elections/{$this->election->id}/candidacy", ['role' => 'sports'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['declare_no_family_link']);
    }

    public function test_candidacy_with_the_declaration_is_recorded(): void
    {
        $this->postJson("/api/v1/cand-test/citizen/association-elections/{$this->election->id}/candidacy", ['role' => 'sports', 'declare_no_family_link' => true])
            ->assertOk();
        $this->assertNotNull(\App\Models\AssociationCandidate::where('citizen_id', $this->citizen->id)->first()->family_link_declared_at);
    }

    public function test_partial_election_refuses_a_candidacy_outside_its_vacant_posts(): void
    {
        $this->election->update(['is_partial' => true, 'scope_roles' => ['treasurer']]);

        $this->postJson("/api/v1/cand-test/citizen/association-elections/{$this->election->id}/candidacy", ['role' => 'sports', 'declare_no_family_link' => true])
            ->assertStatus(422);
        $this->assertSame(0, \App\Models\AssociationCandidate::count());
    }

    public function test_partial_election_accepts_a_candidacy_for_a_vacant_post(): void
    {
        $this->election->update(['is_partial' => true, 'scope_roles' => ['sports']]);

        $this->postJson("/api/v1/cand-test/citizen/association-elections/{$this->election->id}/candidacy", ['role' => 'sports', 'declare_no_family_link' => true])
            ->assertOk();
    }
}
