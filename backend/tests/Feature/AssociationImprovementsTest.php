<?php

namespace Tests\Feature;

use App\Actions\RecordElection;
use App\Mail\AssociationRoleMail;
use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Models\Consulate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Association : liaison du bureau au compte par email, dates de mandat publiques, pagination, élection et bureau saisi à la main. */
class AssociationImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consulate = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
        ]);
        $this->association = Association::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'name' => 'AETT', 'is_public' => true,
        ]);
    }

    private function citizen(string $email, bool $verified = false, ?string $token = null): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Membre', 'email' => $email, 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE,
            'email_verified_at' => $verified ? now() : null,
            'email_verification_token' => $token ? hash('sha256', $token) : null,
        ]);
    }

    public function test_confirming_the_email_links_the_officer_entered_with_that_email(): void
    {
        $token = Str::random(48);
        $citizen = $this->citizen('nouveau@example.test', false, $token);
        $officer = AssociationOfficer::create([
            'association_id' => $this->association->id, 'role' => 'secretary', 'full_name' => 'Nouveau Secrétaire',
            'email' => 'Nouveau@Example.test', 'mandate_start' => now()->toDateString(),
        ]);

        $this->postJson('/api/v1/tchad-togo/citizen/verify-email', ['token' => $token, 'email' => 'nouveau@example.test'])->assertOk();

        $this->assertSame($citizen->id, $officer->fresh()->citizen_id);
    }

    public function test_confirmation_sends_the_appointment_email_once_for_a_current_mandate_only(): void
    {
        Mail::fake();
        $token = Str::random(48);
        $this->citizen('elu@example.test', false, $token);
        AssociationOfficer::create([
            'association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'Élu', 'email' => 'elu@example.test',
            'mandate_start' => now()->subDay()->toDateString(), 'mandate_end' => now()->addYear()->toDateString(),
        ]);
        // Mandat déjà terminé : pas d'email de nomination.
        AssociationOfficer::create([
            'association_id' => $this->association->id, 'role' => 'treasurer', 'full_name' => 'Ancien', 'email' => 'elu@example.test',
            'mandate_start' => '2020-01-01', 'mandate_end' => '2021-01-01',
        ]);

        $this->postJson('/api/v1/tchad-togo/citizen/verify-email', ['token' => $token, 'email' => 'elu@example.test'])->assertOk();

        Mail::assertSent(AssociationRoleMail::class, 1);
        Mail::assertSent(AssociationRoleMail::class, fn ($m) => $m->hasTo('elu@example.test'));
    }

    public function test_an_unverified_or_other_consulate_account_does_not_get_the_officer_linked(): void
    {
        $officer = AssociationOfficer::create([
            'association_id' => $this->association->id, 'role' => 'treasurer', 'full_name' => 'Trésorier', 'email' => 'tres@example.test',
        ]);
        // Compte sans confirmation : aucun lien.
        $this->citizen('tres@example.test');

        $this->assertNull($officer->fresh()->citizen_id);
    }

    public function test_public_api_publishes_mandate_dates_of_the_current_bureau(): void
    {
        AssociationOfficer::create([
            'association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'Présidente',
            'mandate_start' => '2026-01-01', 'mandate_end' => '2027-12-31',
        ]);

        $this->getJson('/api/v1/tchad-togo/association')->assertOk()
            ->assertJsonPath('data.officers.0.mandate_start', '2026-01-01')
            ->assertJsonPath('data.officers.0.mandate_end', '2027-12-31');
    }

    public function test_public_activities_are_paginated(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->association->activities()->create(['title' => "Activité {$i}", 'starts_on' => now()->subDays($i)->toDateString(), 'is_public' => true]);
        }

        $first = $this->getJson('/api/v1/tchad-togo/association')->assertOk();
        $this->assertCount(10, $first->json('data.activities'));
        $this->assertSame(['page' => 1, 'last_page' => 3, 'total' => 25], $first->json('data.activities_page'));

        $last = $this->getJson('/api/v1/tchad-togo/association?page=3')->assertOk();
        $this->assertCount(5, $last->json('data.activities'));

        // Page hors limites : ramenée à la dernière page, jamais d'erreur.
        $this->getJson('/api/v1/tchad-togo/association?page=99')->assertOk()
            ->assertJsonPath('data.activities_page.page', 3);
    }

    public function test_election_closes_a_bureau_entered_by_hand_without_an_election(): void
    {
        $manual = AssociationOfficer::create([
            'association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'Ancien (saisi à la main)',
            'mandate_start' => '2025-01-01',
        ]);
        $this->assertNull($manual->election_id);

        app(RecordElection::class)($this->association, 2026, '2026-06-01', [
            ['role' => 'president', 'full_name' => 'Nouveau président'],
        ]);

        $this->assertSame('2026-05-31', $manual->fresh()->mandate_end->toDateString());
        $this->assertSame(1, AssociationOfficer::where('association_id', $this->association->id)->whereNull('mandate_end')->count());
        $this->assertSame(1, AssociationElection::where('association_id', $this->association->id)->count());
    }
}
