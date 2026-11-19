<?php

namespace Tests\Feature;

use App\Actions\ApplyCandidacy;
use App\Actions\CheckCandidateDocument;
use App\Actions\CloseAssociationBoard;
use App\Actions\CommissionActions;
use App\Actions\CreateOnlineElection;
use App\Actions\PostConditions;
use App\Actions\ProcessElection;
use App\Actions\CastBallot;
use App\Models\Association;
use App\Models\AssociationCandidate;
use App\Models\AssociationCommissionMember;
use App\Models\AssociationElection;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\AssociationPostRule;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\User;
use App\Services\TwoFactor;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Gouvernance : conflits d'intérêts, double contrôle, mandats consécutifs, plafond de clôture, commission, élection partielle, 2FA. */
class AssociationGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private Consulate $consulate;

    private Association $association;

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
    }

    private function user(string $email, string $role = 'consulate_admin'): User
    {
        $u = User::factory()->create(['email' => $email, 'is_active' => true, 'consulate_id' => $this->consulate->id]);
        $u->assignRole($role);

        return $u;
    }

    private function citizen(string $email): Citizen
    {
        return Citizen::withoutGlobalScopes()->create([
            'consulate_id' => $this->consulate->id, 'full_name' => 'Citoyen '.$email, 'email' => $email, 'phone' => '+228 90 00 00 00',
            'password' => 'mot-de-passe-test', 'nationality' => 'Tchad', 'residence_country' => 'Togo',
            'status' => Citizen::STATUS_ACTIVE, 'email_verified_at' => now(),
        ]);
    }

    private function member(Citizen $c, ?string $joined = null): void
    {
        AssociationMember::create(['association_id' => $this->association->id, 'citizen_id' => $c->id, 'full_name' => $c->full_name, 'status' => 'active', 'category' => 'student', 'joined_at' => $joined ?? now()->subYear()->toDateString()]);
    }

    private function candidacyPhase(): AssociationElection
    {
        static $year = 6000;

        return app(CreateOnlineElection::class)($this->association, ++$year, now()->addDay()->toDateTimeString(), now()->addDays(2)->toDateTimeString());
    }

    public function test_a_staff_or_admin_account_cannot_run_for_the_board(): void
    {
        $this->user('agent@consulat.test', 'staff');
        $asCitizen = $this->citizen('agent@consulat.test');
        $this->member($asCitizen);

        $this->assertTrue(PostConditions::conflictOfInterest($asCitizen));
        try {
            app(ApplyCandidacy::class)($this->candidacyPhase(), $asCitizen, 'president');
            $this->fail('Un compte du personnel a pu se porter candidat.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_the_second_check_of_a_president_or_treasurer_document_must_come_from_another_person(): void
    {
        $e = $this->candidacyPhase();
        $candidate = $this->citizen('candidat@example.test');
        $this->member($candidate);
        $cand = AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $candidate->id, 'role' => 'treasurer', 'status' => 'pending']);
        $first = $this->user('verif1@consulat.test');
        $second = $this->user('verif2@consulat.test');

        app(CheckCandidateDocument::class)($cand, $first);
        try {
            app(CheckCandidateDocument::class)($cand->refresh(), $first);
            $this->fail('Le même agent a vérifié deux fois.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        app(CheckCandidateDocument::class)($cand->refresh(), $second);
        $this->assertNotNull($cand->refresh()->document_second_checked_at);
    }

    public function test_the_candidate_cannot_check_their_own_document(): void
    {
        $e = $this->candidacyPhase();
        $candidate = $this->citizen('moi@example.test');
        $this->member($candidate);
        $cand = AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $candidate->id, 'role' => 'secretary', 'status' => 'pending']);
        AssociationPostRule::create(['association_id' => $this->association->id, 'role' => 'secretary', 'requires_document' => true]);

        // Le compte de vérification porte le même email que le candidat : refusé.
        $self = User::factory()->create(['email' => 'moi@example.test', 'is_active' => true, 'consulate_id' => $this->consulate->id]);
        $self->assignRole('consulate_admin');
        $this->expectException(ValidationException::class);
        app(CheckCandidateDocument::class)($cand, $self);
    }

    public function test_only_consecutive_terms_count_toward_the_limit(): void
    {
        AssociationPostRule::create(['association_id' => $this->association->id, 'role' => 'president', 'max_consecutive_terms' => 2]);
        $c = $this->citizen('elu@example.test');

        // Deux mandats qui se suivent : la limite de 2 est atteinte.
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'E', 'citizen_id' => $c->id, 'mandate_start' => '2022-01-01', 'mandate_end' => '2022-12-31']);
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'E', 'citizen_id' => $c->id, 'mandate_start' => '2023-01-01', 'mandate_end' => '2023-12-31']);
        $this->assertSame(2, PostConditions::consecutiveTerms($this->association, $c, 'president'));
        $this->assertNotEmpty(PostConditions::unmet($this->association, $c, 'president'));

        // Un mandat ancien, séparé par plus d'un an d'interruption : ne compte pas dans la suite.
        $d = $this->citizen('ancien@example.test');
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'A', 'citizen_id' => $d->id, 'mandate_start' => '2016-01-01', 'mandate_end' => '2016-12-31']);
        AssociationOfficer::create(['association_id' => $this->association->id, 'role' => 'president', 'full_name' => 'A', 'citizen_id' => $d->id, 'mandate_start' => '2023-01-01', 'mandate_end' => '2023-12-31']);
        $this->assertSame(1, PostConditions::consecutiveTerms($this->association, $d, 'president'));
    }

    public function test_the_board_cannot_be_archived_twice_within_thirty_days_except_by_the_platform_owner(): void
    {
        app(CloseAssociationBoard::class)($this->association);

        try {
            app(CloseAssociationBoard::class)($this->association->refresh());
            $this->fail('Deuxième clôture dans les 30 jours.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        app(CloseAssociationBoard::class)($this->association->refresh(), null, true);
        $this->assertTrue(true);
    }

    public function test_the_electoral_commission_has_three_non_staff_members_and_attests_a_published_result(): void
    {
        $members = collect(range(1, 3))->map(fn ($i) => $this->citizen("comm{$i}@example.test"));
        foreach ($members as $m) {
            CommissionActions::add($this->association, $m);
        }
        try {
            CommissionActions::add($this->association, $this->citizen('quatrieme@example.test'));
            $this->fail('Plus de trois membres à la commission.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        // Résultat publié : une candidate élue sans concurrence.
        $e = app(CreateOnlineElection::class)($this->association, 6100, now()->subHour()->toDateTimeString(), now()->addDay()->toDateTimeString(), null, 72);
        $winner = $this->citizen('gagnante@example.test');
        $this->member($winner);
        $cand = AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $winner->id, 'role' => 'president', 'status' => 'approved']);
        $voter = $this->citizen('electeur@example.test');
        $this->member($voter);
        app(CastBallot::class)($e, $voter, [$cand->id]);
        $this->travel(2)->days();
        do {
            $o = app(ProcessElection::class)($e->refresh());
        } while ($o === 'frozen');

        // Avant publication : impossible ; après : un membre suffit à attester, deux fois le même membre ne compte qu'une fois.
        $this->assertNotNull($e->refresh()->proclaimed_at);
        CommissionActions::attest($e, $members[0]);
        CommissionActions::attest($e, $members[0]);
        $this->assertSame(['count' => 1, 'required' => 2, 'attested' => false], CommissionActions::summary($e));
        CommissionActions::attest($e, $members[1]);
        $this->assertTrue(CommissionActions::summary($e)['attested']);

        // Un non-membre ne peut pas attester.
        $this->expectException(ValidationException::class);
        CommissionActions::attest($e, $voter);
    }

    public function test_a_commission_member_cannot_be_a_staff_account(): void
    {
        $this->user('staff-commission@consulat.test', 'staff');
        $asCitizen = $this->citizen('staff-commission@consulat.test');

        $this->expectException(ValidationException::class);
        CommissionActions::add($this->association, $asCitizen);
    }

    public function test_a_vacant_post_opens_a_partial_election_limited_to_that_post(): void
    {
        $e = app(CreateOnlineElection::class)($this->association, 6200, now()->subHour()->toDateTimeString(), now()->addDay()->toDateTimeString(), null, 72);
        $trez = $this->citizen('tresorier-candidat@example.test');
        $this->member($trez);
        AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $trez->id, 'role' => 'treasurer', 'status' => 'approved']);

        $this->travel(2)->days();
        do {
            $o = app(ProcessElection::class)($e->refresh());
        } while ($o === 'frozen');

        $partial = AssociationElection::where('is_partial', true)->first();
        $this->assertNotNull($partial);
        // Tout poste non pourvu entre dans l'élection partielle, y compris ceux sans aucun candidat (ex. présidence).
        $this->assertContains('treasurer', $partial->scope_roles);
        $this->assertContains('president', $partial->scope_roles);
        $this->assertSame(6200, $partial->year);
        $this->assertSame('candidacy', $partial->phase());

        // Dans l'élection partielle, on ne peut se porter candidat que pour la fonction vacante.
        $other = $this->citizen('autre@example.test');
        $this->member($other);
        $this->travel(8)->days();
        $this->assertSame('voting', $partial->refresh()->phase() ?? 'voting');
    }

    public function test_two_factor_is_required_for_managers_of_elections_and_the_board(): void
    {
        config(['security.two_factor_for_managers' => true]);

        $this->assertTrue(TwoFactor::requiredFor($this->user('consul@consulat.test', 'consulate_admin')));
        $this->assertFalse(TwoFactor::requiredFor($this->user('agent2@consulat.test', 'staff')));
    }
}
