<?php

namespace Tests\Feature;

use App\Actions\LedgerActions;
use App\Models\Association;
use App\Models\AssociationLedgerEntry;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Registre financier : cotisations par le trésorier, dépenses proposées puis approuvées par un autre membre, solde, écritures figées, publication anonyme. */
class AssociationLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Association $association;

    private Citizen $treasurer;

    private Citizen $president;

    private Citizen $ordinary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $c = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->association = Association::withoutGlobalScopes()->create(['consulate_id' => $c->id, 'name' => 'AETT', 'is_public' => true]);
        $this->treasurer = $this->citizen($c, 'tresorier@example.test');
        $this->president = $this->citizen($c, 'president@example.test');
        $this->ordinary = $this->citizen($c, 'simple@example.test');
        $this->officer('treasurer', $this->treasurer);
        $this->officer('president', $this->president);
    }

    private function citizen(Consulate $c, string $email): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $c->id, 'full_name' => $email, 'email' => $email, 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    private function officer(string $role, Citizen $c): void
    {
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => $role, 'full_name' => $c->full_name, 'citizen_id' => $c->id, 'mandate_start' => now()->subMonth()->toDateString()]);
    }

    public function test_only_the_treasurer_records_contributions(): void
    {
        $entry = LedgerActions::recordContribution($this->association, $this->treasurer, 5000, 'Cotisation 2026', now()->toDateString());
        $this->assertSame(5000, LedgerActions::balance($this->association));
        $this->assertSame('approved', $entry->status);

        $this->expectException(ValidationException::class);
        LedgerActions::recordContribution($this->association, $this->president, 1000, 'Pas le trésorier', now()->toDateString());
    }

    public function test_an_expense_needs_a_second_board_member_to_approve_it(): void
    {
        LedgerActions::recordContribution($this->association, $this->treasurer, 10000, 'Cotisations', now()->toDateString());
        $expense = LedgerActions::proposeExpense($this->association, $this->president, 3000, 'Fournitures', now()->toDateString());

        try {
            LedgerActions::approveExpense($expense, $this->president);
            $this->fail('Une dépense approuvée par celui qui la propose.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        LedgerActions::approveExpense($expense->refresh(), $this->treasurer);
        $this->assertSame('approved', $expense->refresh()->status);
        $this->assertSame(7000, LedgerActions::balance($this->association));
    }

    public function test_an_ordinary_citizen_cannot_propose_or_approve_expenses(): void
    {
        LedgerActions::recordContribution($this->association, $this->treasurer, 10000, 'Cotisations', now()->toDateString());
        $this->expectException(ValidationException::class);
        LedgerActions::proposeExpense($this->association, $this->ordinary, 100, 'Non autorisé', now()->toDateString());
    }

    public function test_an_expense_cannot_make_the_balance_negative(): void
    {
        LedgerActions::recordContribution($this->association, $this->treasurer, 1000, 'Cotisations', now()->toDateString());
        $expense = LedgerActions::proposeExpense($this->association, $this->president, 5000, 'Trop cher', now()->toDateString());

        $this->expectException(ValidationException::class);
        LedgerActions::approveExpense($expense, $this->treasurer);
    }

    public function test_an_approved_entry_cannot_be_changed_or_deleted(): void
    {
        $entry = LedgerActions::recordContribution($this->association, $this->treasurer, 1000, 'Cotisation', now()->toDateString());

        try {
            $entry->update(['amount' => 999999]);
            $this->fail('Une écriture approuvée a été modifiée.');
        } catch (\LogicException) {
            $this->assertTrue(true);
        }
        $this->expectException(\LogicException::class);
        $entry->delete();
    }

    public function test_the_members_view_shows_rights_and_who_can_approve_what(): void
    {
        LedgerActions::recordContribution($this->association, $this->treasurer, 10000, 'Cotisations', now()->toDateString());
        $expense = LedgerActions::proposeExpense($this->association, $this->president, 2000, 'Fournitures', now()->toDateString());

        Sanctum::actingAs($this->treasurer);
        $view = $this->getJson('/api/v1/tchad-togo/citizen/association/ledger')->assertOk()->json('data');
        $this->assertSame('treasurer', $view['role']);
        $this->assertTrue($view['can_record_contribution']);
        $pending = collect($view['entries'])->firstWhere('id', $expense->id);
        $this->assertTrue($pending['can_approve']);

        Sanctum::actingAs($this->president);
        $own = collect($this->getJson('/api/v1/tchad-togo/citizen/association/ledger')->json('data.entries'))->firstWhere('id', $expense->id);
        $this->assertFalse($own['can_approve']);

        Sanctum::actingAs($this->ordinary);
        $this->assertNull($this->getJson('/api/v1/tchad-togo/citizen/association/ledger')->json('data.role'));
    }

    public function test_the_public_csv_export_has_the_approved_entries_and_no_personal_data(): void
    {
        LedgerActions::recordContribution($this->association, $this->treasurer, 7500, 'Cotisation exportée', now()->toDateString());

        $response = $this->get('/api/v1/tchad-togo/association/export.csv')->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $body = $response->getContent();
        $this->assertStringContainsString('Cotisation exportée', $body);
        $this->assertStringNotContainsString('@example.test', $body);
    }

    public function test_the_public_ledger_shows_balance_and_entries_without_any_name(): void
    {
        LedgerActions::recordContribution($this->association, $this->treasurer, 4000, 'Cotisation publique', now()->toDateString());

        $json = $this->getJson('/api/v1/tchad-togo/association/ledger')->assertOk()->json('data');
        $this->assertSame(4000, $json['balance']);
        $this->assertSame('Cotisation publique', $json['entries'][0]['label']);
        $this->assertStringNotContainsString('@example.test', json_encode($json));
        $this->assertArrayNotHasKey('proposed_by', $json['entries'][0]);
        $this->assertSame(1, AssociationLedgerEntry::count());
    }
}
