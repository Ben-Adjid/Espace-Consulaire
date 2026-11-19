<?php

namespace Tests\Feature\Api;

use App\Models\Citizen;
use App\Models\CitizenReport;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Signalements : envoi par le citoyen, suivi, et cloisonnement des signalements d'intégrité. */
class CitizenReportTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Citizen $citizen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->consulate = Consulate::create([
            'slug' => 'tchad-cameroun', 'ref_prefix' => 'TCH-CM', 'name' => ['fr' => 'Tchad au Cameroun'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Cameroun'],
            'covered_countries' => ['Cameroun'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->citizen = Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Citoyen', 'email' => 'c@example.test', 'phone' => '+235 66 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Cameroun',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    public function test_citizen_sends_a_report_and_follows_it(): void
    {
        Sanctum::actingAs($this->citizen);

        $this->postJson('/api/v1/tchad-cameroun/citizen/reports', [
            'category' => 'delay', 'subject' => 'Passeport en retard', 'message' => 'Mon passeport devait être prêt depuis un mois.',
        ])->assertCreated();

        $this->getJson('/api/v1/tchad-cameroun/citizen/reports')->assertOk()
            ->assertJsonPath('data.0.subject', 'Passeport en retard')
            ->assertJsonPath('data.0.status', 'new');
    }

    public function test_reports_require_an_account(): void
    {
        $this->postJson('/api/v1/tchad-cameroun/citizen/reports', [
            'category' => 'delay', 'subject' => 'Sans compte', 'message' => 'Message envoyé sans être connecté.',
        ])->assertUnauthorized();
    }

    public function test_integrity_reports_are_hidden_from_consulate_staff(): void
    {
        CitizenReport::withoutGlobalScopes()->create(['consulate_id' => $this->consulate->id, 'category' => 'integrity', 'subject' => 'Demande d’argent', 'message' => 'Un agent a demandé un paiement non officiel.']);
        CitizenReport::withoutGlobalScopes()->create(['consulate_id' => $this->consulate->id, 'category' => 'reception', 'subject' => 'Accueil', 'message' => 'Attente très longue au guichet.']);

        $staff = User::create(['name' => 'Agent', 'email' => 'agent@example.test', 'password' => bcrypt('x'), 'consulate_id' => $this->consulate->id, 'is_active' => true]);
        $staff->assignRole('consulate_admin');
        $super = User::create(['name' => 'Super', 'email' => 'super@example.test', 'password' => bcrypt('x'), 'is_active' => true]);
        $super->assignRole('super_admin');

        $this->assertSame(['Accueil'], CitizenReport::withoutGlobalScopes()->visibleTo($staff)->pluck('subject')->all());
        $this->assertCount(2, CitizenReport::withoutGlobalScopes()->visibleTo($super)->get());
    }
}
