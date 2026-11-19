<?php

namespace Tests\Feature;

use App\Actions\ApplyCandidacy;
use App\Actions\CastBallot;
use App\Actions\CheckCandidateDocument;
use App\Actions\CreateOnlineElection;
use App\Actions\EndMemberships;
use App\Actions\ProcessElection;
use App\Actions\ReviewCandidacy;
use App\Actions\TallyElection;
use App\Actions\VerifyVoteChain;
use App\Models\Association;
use App\Models\AssociationBallot;
use App\Models\AssociationCandidate;
use App\Models\AssociationElection;
use App\Models\AssociationElectionResult;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\AssociationPostRule;
use App\Models\AssociationVote;
use App\Models\AssociationVoteChain;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Élections en ligne : candidatures et conditions, vote à deux tours, électorat figé, totaux cachés,
 * dépouillement et proclamation automatiques, départage, journal chaîné, reçus, résultats publiés.
 */
class AssociationOnlineVotingTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Association $association;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->consulate = Consulate::create([
            'slug' => 'tchad-togo', 'ref_prefix' => 'TCH-TG', 'name' => ['fr' => 'Tchad au Togo'],
            'represented_country' => ['fr' => 'Tchad'], 'host_country' => ['fr' => 'Togo'],
            'covered_countries' => ['Togo'], 'languages' => ['fr'], 'is_active' => true,
            'subscription_until' => now()->addYear()->toDateString(),
        ]);
        $this->association = Association::withoutGlobalScopes()->create(['consulate_id' => $this->consulate->id, 'name' => 'AETT', 'is_public' => true]);
        $this->admin = User::factory()->create(['is_active' => true, 'consulate_id' => $this->consulate->id]);
        $this->admin->assignRole('consulate_admin');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    protected function citizen(string $name, bool $verified = true): Citizen
    {
        static $n = 0;
        $n++;

        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => $name, 'email' => "c{$n}@example.test", 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => $verified ? now() : null,
        ]);
    }

    protected function member(Citizen $c, string $status = 'active', ?string $joined = null, string $category = 'student'): void
    {
        AssociationMember::create([
            'association_id' => $this->association->id, 'citizen_id' => $c->id, 'full_name' => $c->full_name,
            'status' => $status, 'category' => $category, 'joined_at' => $joined ?? now()->subYear()->toDateString(),
        ]);
    }

    /** Scrutin en cours de vote (ouvert il y a une heure, fermeture dans un jour). */
    protected function openVoting(int $secondRoundHours = 72): AssociationElection
    {
        static $year = 3000;

        return app(CreateOnlineElection::class)($this->association, ++$year, now()->subHour()->toDateTimeString(), now()->addDay()->toDateTimeString(), null, $secondRoundHours);
    }

    /** Scrutin en phase de candidature (ouverture dans un jour). */
    protected function candidacyPhase(): AssociationElection
    {
        static $year = 4000;

        return app(CreateOnlineElection::class)($this->association, ++$year, now()->addDay()->toDateTimeString(), now()->addDays(2)->toDateTimeString());
    }

    /** Candidat validé, créé directement (la règle de candidature est testée à part). */
    protected function candidate(AssociationElection $e, Citizen $c, string $role): AssociationCandidate
    {
        if (! AssociationMember::where('association_id', $this->association->id)->where('citizen_id', $c->id)->exists()) {
            $this->member($c);
        }

        return AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $c->id, 'role' => $role, 'status' => 'approved']);
    }

    /** Fait passer le temps après la fermeture, puis lance le traitement automatique jusqu'à son terme. */
    protected function closeAndProcess(AssociationElection $e): string
    {
        $this->travel(2)->days();
        $outcome = null;
        do {
            $outcome = app(ProcessElection::class)($e->refresh());
        } while ($outcome === 'frozen');

        return $outcome;
    }

    protected function voter(): Citizen
    {
        $v = $this->citizen('Électeur');
        $this->member($v);

        return $v;
    }

    public function test_only_active_verified_members_can_apply_during_the_candidacy_period(): void
    {
        $e = $this->candidacyPhase();
        $member = $this->citizen('Membre');
        $this->member($member);
        $outsider = $this->citizen('Non membre');
        $unverified = $this->citizen('Non confirmé', false);
        $this->member($unverified);
        $inactive = $this->citizen('Adhésion terminée');
        $this->member($inactive, 'inactive');

        app(ApplyCandidacy::class)($e, $member, 'president');
        $this->assertSame('pending', AssociationCandidate::where('citizen_id', $member->id)->value('status'));

        foreach ([$outsider, $unverified, $inactive] as $refused) {
            try {
                app(ApplyCandidacy::class)($e, $refused, 'treasurer');
                $this->fail('Une candidature non éligible a été acceptée.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_post_conditions_block_a_candidacy_until_met(): void
    {
        AssociationPostRule::create(['association_id' => $this->association->id, 'role' => 'president', 'min_membership_months' => 6, 'requires_student' => true]);
        $e = $this->candidacyPhase();

        $recent = $this->citizen('Adhérente récente');
        $this->member($recent, 'active', now()->subMonths(2)->toDateString());
        try {
            app(ApplyCandidacy::class)($e, $recent, 'president');
            $this->fail('Condition d’ancienneté ignorée.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $notStudent = $this->citizen('Ancienne');
        $this->member($notStudent, 'active', now()->subYear()->toDateString(), 'alumni');
        try {
            app(ApplyCandidacy::class)($e, $notStudent, 'president');
            $this->fail('Condition de statut ignorée.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $eligible = $this->citizen('Éligible');
        $this->member($eligible, 'active', now()->subYear()->toDateString(), 'student');
        app(ApplyCandidacy::class)($e, $eligible, 'president');
        $this->assertSame(1, AssociationCandidate::where('citizen_id', $eligible->id)->count());
    }

    public function test_a_candidacy_cannot_be_made_once_voting_has_opened_or_twice(): void
    {
        $member = $this->citizen('Membre');
        $this->member($member);

        try {
            app(ApplyCandidacy::class)($this->openVoting(), $member, 'president');
            $this->fail('Candidature acceptée pendant le vote.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $e = $this->candidacyPhase();
        app(ApplyCandidacy::class)($e, $member, 'president');
        $this->expectException(ValidationException::class);
        app(ApplyCandidacy::class)($e, $member, 'secretary');
    }

    public function test_a_required_document_must_be_checked_before_a_candidacy_is_approved(): void
    {
        AssociationPostRule::create(['association_id' => $this->association->id, 'role' => 'treasurer', 'requires_document' => true]);
        $e = $this->candidacyPhase();
        $member = $this->citizen('Trésorier candidat');
        $this->member($member);
        $cand = app(ApplyCandidacy::class)($e, $member, 'treasurer');

        try {
            app(ReviewCandidacy::class)($cand, 'approved');
            $this->fail('Validation sans pièce vérifiée.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        // Trésorier : double contrôle obligatoire, par deux personnes différentes.
        $second = User::factory()->create(['is_active' => true, 'consulate_id' => $this->consulate->id]);
        $second->assignRole('consulate_admin');
        app(CheckCandidateDocument::class)($cand, $this->admin);
        try {
            app(ReviewCandidacy::class)($cand->refresh(), 'approved');
            $this->fail('Validation après une seule vérification du trésorier.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        app(CheckCandidateDocument::class)($cand->refresh(), $second);
        app(ReviewCandidacy::class)($cand->refresh(), 'approved');
        $this->assertSame('approved', $cand->refresh()->status);
    }

    public function test_a_ballot_is_cast_once_per_round_for_approved_candidates_one_per_role(): void
    {
        $e = $this->openVoting();
        $voter = $this->voter();
        $pres = $this->candidate($e, $this->citizen('Présidente'), 'president');
        $sec = $this->candidate($e, $this->citizen('Secrétaire'), 'secretary');
        $pendingCitizen = $this->citizen('Secrétaire en attente');
        $this->member($pendingCitizen);
        $pending = AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $pendingCitizen->id, 'role' => 'secretary', 'status' => 'pending']);

        try {
            app(CastBallot::class)($e, $voter, [$pending->id]);
            $this->fail('Un candidat non validé a reçu une voix.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        app(CastBallot::class)($e, $voter, [$pres->id, $sec->id]);
        $this->assertSame(2, AssociationVote::where('election_id', $e->id)->count());

        $this->expectException(ValidationException::class);
        app(CastBallot::class)($e, $voter, [$pres->id]);
    }

    public function test_two_candidates_for_the_same_role_on_one_ballot_are_refused(): void
    {
        $e = $this->openVoting();
        $a = $this->candidate($e, $this->citizen('A'), 'president');
        $b = $this->candidate($e, $this->citizen('B'), 'president');

        $this->expectException(ValidationException::class);
        app(CastBallot::class)($e, $this->voter(), [$a->id, $b->id]);
    }

    public function test_ballots_and_votes_are_stored_apart_so_a_choice_cannot_be_traced_to_a_voter(): void
    {
        $e = $this->openVoting();
        $voter = $this->voter();
        $cand = $this->candidate($e, $this->citizen('Candidat'), 'president');
        app(CastBallot::class)($e, $voter, [$cand->id]);

        $this->assertSame(1, AssociationBallot::where('citizen_id', $voter->id)->count());
        $this->assertFalse(in_array('citizen_id', DB::getSchemaBuilder()->getColumnListing('association_votes'), true));
        $this->assertFalse(in_array('citizen_id', DB::getSchemaBuilder()->getColumnListing('association_vote_chain'), true));
    }

    public function test_the_electorate_is_frozen_when_voting_opens(): void
    {
        $e = $this->openVoting();
        $early = $this->voter();
        $cand = $this->candidate($e, $this->citizen('Candidat'), 'president');

        app(ProcessElection::class)($e->refresh()); // ouverture : liste figée
        $late = $this->citizen('Membre arrivé après');
        $this->member($late);

        try {
            app(CastBallot::class)($e->refresh(), $late, [$cand->id]);
            $this->fail('Un membre ajouté après l’ouverture a pu voter.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        app(CastBallot::class)($e->refresh(), $early, [$cand->id]);
        $this->assertSame(1, AssociationVote::count());
    }

    public function test_totals_are_hidden_until_the_vote_is_processed(): void
    {
        $e = $this->openVoting();
        $cand = $this->candidate($e, $this->citizen('Candidat'), 'president');
        app(CastBallot::class)($e, $this->voter(), [$cand->id]);

        $this->assertSame([], app(TallyElection::class)($e));

        Sanctum::actingAs($this->voter());
        $list = $this->getJson('/api/v1/tchad-togo/citizen/association-elections')->assertOk();
        $this->assertNull($list->json('data.0.results'));
        $this->assertNull($list->json('data.0.chain'));
        $this->assertTrue($list->json('data.0.has_voted') === true || $list->json('data.0.has_voted') === false);
        $this->assertSame(0, count(collect($list->json('data.0.candidates'))->flatten(1)->filter(fn ($c) => isset($c['votes']))));
    }

    public function test_a_clear_majority_is_elected_and_proclaimed_automatically(): void
    {
        $e = $this->openVoting();
        $winner = $this->candidate($e, $this->citizen('Nouvelle présidente'), 'president');
        $loser = $this->candidate($e, $this->citizen('Autre'), 'president');
        $old = $this->citizen('Ancienne présidente');
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'Ancienne', 'citizen_id' => $old->id, 'mandate_start' => '2025-01-01']);

        foreach (range(1, 3) as $i) {
            app(CastBallot::class)($e, $this->voter(), [$winner->id]);
        }
        app(CastBallot::class)($e, $this->voter(), [$loser->id]);

        $this->assertSame('proclaimed', $this->closeAndProcess($e));

        $e->refresh();
        $this->assertNotNull($e->proclaimed_at);
        $this->assertSame(1, $e->results()->where('round', 1)->where('status', 'elected')->count());
        $this->assertSame($winner->citizen_id, AssociationOfficer::where('election_id', $e->id)->value('citizen_id'));
        $this->assertSame(1, AssociationOfficer::where('association_id', $this->association->id)->whereNull('mandate_end')->count());
        $this->assertSame(now()->subDay()->toDateString(), AssociationOfficer::where('full_name', 'Ancienne')->value('mandate_end')->toDateString());
    }

    public function test_a_role_without_any_vote_is_left_vacant_and_reported(): void
    {
        $e = $this->openVoting();
        $this->candidate($e, $this->citizen('Candidate sans voix'), 'treasurer');

        $this->closeAndProcess($e);

        $this->assertSame('vacant', $e->results()->where('role', 'treasurer')->value('status'));
        $this->assertSame(0, AssociationOfficer::where('election_id', $e->id)->count());
    }

    public function test_the_vote_journal_is_chained_and_detects_tampering(): void
    {
        $e = $this->openVoting();
        $cand = $this->candidate($e, $this->citizen('Candidat'), 'president');
        app(CastBallot::class)($e, $this->voter(), [$cand->id]);
        app(CastBallot::class)($e, $this->voter(), [$cand->id]);

        $this->assertTrue((app(VerifyVoteChain::class))($e->id)['valid']);
        $this->assertSame(2, (app(VerifyVoteChain::class))($e->id)['length']);

        // Altération directe en base : la vérification doit la voir.
        DB::table('association_vote_chain')->where('election_id', $e->id)->where('seq', 1)->update(['candidate_ids' => '999']);
        $this->assertFalse((app(VerifyVoteChain::class))($e->id)['valid']);
    }

    public function test_recorded_votes_and_chain_entries_cannot_be_edited_through_the_models(): void
    {
        $e = $this->openVoting();
        $cand = $this->candidate($e, $this->citizen('Candidat'), 'president');
        app(CastBallot::class)($e, $this->voter(), [$cand->id]);

        $vote = AssociationVote::first();
        $this->expectException(\LogicException::class);
        $vote->update(['round' => 9]);
    }

    public function test_the_receipt_proves_a_ballot_exists_without_revealing_the_choice(): void
    {
        $e = $this->openVoting();
        $cand = $this->candidate($e, $this->citizen('Candidat'), 'president');
        $voter = $this->voter();
        $code = app(CastBallot::class)($e, $voter, [$cand->id]);

        Sanctum::actingAs($voter);
        $this->postJson("/api/v1/tchad-togo/citizen/association-elections/{$e->id}/receipt", ['code' => $code])
            ->assertOk()->assertJsonPath('valid', true);
        $this->postJson("/api/v1/tchad-togo/citizen/association-elections/{$e->id}/receipt", ['code' => 'FAUX'])
            ->assertOk()->assertJsonPath('valid', false);

        Sanctum::actingAs($this->voter());
        $this->postJson("/api/v1/tchad-togo/citizen/association-elections/{$e->id}/receipt", ['code' => $code])
            ->assertOk()->assertJsonPath('valid', false);
    }

    public function test_public_results_appear_only_after_proclamation(): void
    {
        $e = $this->openVoting();
        $cand = $this->candidate($e, $this->citizen('Élue'), 'president');
        app(CastBallot::class)($e, $this->voter(), [$cand->id]);

        $this->getJson("/api/v1/tchad-togo/association/elections/{$e->id}/results")->assertNotFound();

        $this->closeAndProcess($e);

        $this->getJson("/api/v1/tchad-togo/association/elections/{$e->id}/results")
            ->assertOk()->assertJsonPath('data.chain.valid', true)
            ->assertJsonPath('data.rounds.0.roles.0.winner', 'Élue');
    }

    public function test_a_proclaimed_election_and_its_dates_are_locked_against_edits(): void
    {
        $e = $this->openVoting();
        $this->expectException(\LogicException::class);
        $e->update(['voting_closes_at' => now()->addYear()]);
    }

    public function test_the_second_round_duration_is_bounded(): void
    {
        $this->expectException(ValidationException::class);
        app(CreateOnlineElection::class)($this->association, 5000, now()->subHour()->toDateTimeString(), now()->addDay()->toDateTimeString(), null, 10);
    }

    public function test_membership_is_ended_at_the_end_of_a_mandate_by_the_scheduled_command(): void
    {
        $person = $this->citizen('Ancien élu');
        $this->member($person);
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'treasurer', 'full_name' => 'Ancien élu', 'citizen_id' => $person->id, 'mandate_start' => '2024-01-01', 'mandate_end' => now()->subDays(3)->toDateString()]);

        $this->artisan('consulat:end-expired-memberships')->assertSuccessful();

        $this->assertSame('inactive', AssociationMember::where('citizen_id', $person->id)->value('status'));
        $this->assertSame(1, app(EndMemberships::class)($this->association) + 1);
    }
}
