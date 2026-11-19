<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\AssociationBoardLog;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Lot 3 : export du registre sans contacts, journal des actions du bureau, rappels de fin de mandat. */
class BoardLot3Test extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Association $association;

    private Citizen $president;

    private Citizen $ordinary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consulate = Consulate::create([
            'slug' => 'lot3-test', 'ref_prefix' => 'LT3', 'name' => ['fr' => 'Lot 3'],
            'represented_country' => ['fr' => 'Test'], 'host_country' => ['fr' => 'Test'],
            'covered_countries' => ['Test'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->association = Association::withoutGlobalScopes()->create(['consulate_id' => $this->consulate->id, 'name' => 'AETEST', 'is_public' => true]);
        $make = fn (string $email) => Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => $email, 'email' => $email, 'phone' => '+000 00 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Test', 'residence_country' => 'Test',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
        $this->president = $make('president@example.test');
        $this->ordinary = $make('simple@example.test');
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'Président', 'citizen_id' => $this->president->id, 'mandate_start' => now()->subMonth(), 'mandate_end' => now()->addYear()]);
        AssociationMember::create(['association_id' => $this->association->id, 'full_name' => 'Adhérent Un', 'email' => 'adherent@example.test', 'phone' => '+000 11 22 33 44', 'status' => 'active', 'category' => 'student', 'joined_at' => now()->subYear()]);
    }

    public function test_registry_export_contains_no_contacts_and_is_logged(): void
    {
        Sanctum::actingAs($this->president);
        $response = $this->get('/api/v1/lot3-test/citizen/association/members/export.csv')->assertOk();

        $csv = $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Adhérent Un', $csv);
        $this->assertStringNotContainsString('@', $csv);
        $this->assertStringNotContainsString('+000', $csv);
        $this->assertTrue(AssociationBoardLog::where('action', 'export')->exists());
    }

    public function test_ordinary_member_cannot_export_the_registry(): void
    {
        Sanctum::actingAs($this->ordinary);
        $this->get('/api/v1/lot3-test/citizen/association/members/export.csv')->assertForbidden();
    }

    public function test_board_journal_records_board_actions_for_the_board_only(): void
    {
        Sanctum::actingAs($this->president);
        $this->postJson('/api/v1/lot3-test/citizen/association/activities', [
            'title' => 'Journée sportive', 'starts_on' => now()->addWeek()->toDateString(),
        ])->assertCreated();

        $json = $this->getJson('/api/v1/lot3-test/citizen/association/journal')->assertOk()->json();
        $this->assertSame('activity', $json['data'][0]['action']);
        $this->assertSame('president@example.test', $json['data'][0]['by']);
        $this->assertStringContainsString('Journée sportive', $json['data'][0]['label']);

        Sanctum::actingAs($this->ordinary);
        $this->getJson('/api/v1/lot3-test/citizen/association/journal')->assertForbidden();
    }

    public function test_board_journal_entries_cannot_be_changed_or_deleted(): void
    {
        $log = AssociationBoardLog::create(['association_id' => $this->association->id, 'citizen_id' => $this->president->id, 'action' => 'info', 'label' => 'test']);

        $this->expectException(\LogicException::class);
        $log->update(['label' => 'modifié']);
    }

    public function test_mandate_end_reminder_sent_once_per_day(): void
    {
        // Mandat qui se termine dans 10 jours : un rappel, puis rien le même jour.
        AssociationOfficer::where('role', 'president')->update(['mandate_end' => now()->addDays(10)->toDateString(), 'email' => 'president@example.test']);

        Mail::shouldReceive('raw')->once()->andReturnNull();

        $this->artisan('consulat:remind-mandates')->assertExitCode(0);
        $this->artisan('consulat:remind-mandates')->assertExitCode(0);
    }

    public function test_no_reminder_when_mandate_is_far_from_its_end(): void
    {
        AssociationOfficer::where('role', 'president')->update(['mandate_end' => now()->addYear()->toDateString(), 'email' => 'president@example.test']);

        Mail::shouldReceive('raw')->never();

        $this->artisan('consulat:remind-mandates')->assertExitCode(0);
    }
}
