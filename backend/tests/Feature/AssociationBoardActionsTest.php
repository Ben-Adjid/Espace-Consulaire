<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\AssociationActivity;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Droits du bureau : chaque fonction n'agit que dans sa mission. */
class AssociationBoardActionsTest extends TestCase
{
    use RefreshDatabase;

    private Association $association;

    private Consulate $c;

    protected function setUp(): void
    {
        parent::setUp();
        $this->c = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->association = Association::withoutGlobalScopes()->create(['consulate_id' => $this->c->id, 'name' => 'AETT', 'is_public' => true]);
    }

    private function officer(string $role): Citizen
    {
        $c = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => $role, 'email' => $role.'@example.test', 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => $role, 'full_name' => $role, 'citizen_id' => $c->id, 'mandate_start' => now()->subMonth()]);

        return $c;
    }

    public function test_the_sports_officer_publishes_activities_but_the_treasurer_cannot(): void
    {
        $sports = $this->officer('sports');
        $treasurer = $this->officer('treasurer');
        $body = ['title' => 'Tournoi de football', 'starts_on' => now()->addWeek()->toDateString(), 'location' => 'Lomé'];

        Sanctum::actingAs($sports);
        $this->postJson('/api/v1/tchad-togo/citizen/association/activities', $body)->assertCreated();
        $this->assertSame(1, AssociationActivity::where('title', 'Tournoi de football')->where('is_public', true)->count());

        Sanctum::actingAs($treasurer);
        $this->postJson('/api/v1/tchad-togo/citizen/association/activities', $body)->assertForbidden();
    }

    public function test_only_communication_or_presidency_edit_public_information(): void
    {
        $communication = $this->officer('communication');
        $sports = $this->officer('sports');

        Sanctum::actingAs($sports);
        $this->patchJson('/api/v1/tchad-togo/citizen/association/info', ['description' => 'Test'])->assertForbidden();

        Sanctum::actingAs($communication);
        $this->patchJson('/api/v1/tchad-togo/citizen/association/info', ['description' => 'Nouvelle description'])->assertOk();
        $this->assertSame('Nouvelle description', $this->association->refresh()->description);
    }

    public function test_every_board_member_sees_the_dashboard_and_ordinary_members_do_not(): void
    {
        $relations = $this->officer('external_relations');
        Sanctum::actingAs($relations);
        $this->getJson('/api/v1/tchad-togo/citizen/association/board')->assertOk()->assertJsonPath('data.role', 'external_relations');

        $ordinary = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->c->id, 'full_name' => 'Simple', 'email' => 'simple@example.test', 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($ordinary);
        $this->getJson('/api/v1/tchad-togo/citizen/association/board')->assertForbidden();
    }
}
