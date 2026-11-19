<?php

namespace Tests\Feature;

use App\Actions\CastBallot;
use App\Models\AssociationCandidate;
use App\Models\AssociationElectionResult;
use App\Models\AssociationOfficer;
use App\Models\AssociationVote;
use Illuminate\Validation\ValidationException;

/**
 * Second tour : ouverture automatique entre les deux premiers, électeurs inchangés (liste figée), départage par ancienneté.
 * Ces scénarios réutilisent les mêmes électeurs d'un tour à l'autre : la liste est figée à l'ouverture du vote.
 */
class AssociationSecondRoundTest extends AssociationOnlineVotingTest
{
    public function test_without_a_majority_a_second_round_opens_between_the_two_leaders(): void
    {
        $e = $this->openVoting(48);
        $a = $this->candidate($e, $this->citizen('A'), 'president');
        $b = $this->candidate($e, $this->citizen('B'), 'president');
        $c = $this->candidate($e, $this->citizen('C'), 'president');
        $voters = [$this->voter(), $this->voter(), $this->voter(), $this->voter(), $this->voter()];

        app(CastBallot::class)($e, $voters[0], [$a->id]);
        app(CastBallot::class)($e, $voters[1], [$a->id]);
        app(CastBallot::class)($e, $voters[2], [$b->id]);
        app(CastBallot::class)($e, $voters[3], [$b->id]);
        app(CastBallot::class)($e, $voters[4], [$c->id]);

        $this->assertSame('second_round', $this->closeAndProcess($e));

        $e->refresh();
        $this->assertSame(2, $e->current_round);
        $this->assertNull($e->proclaimed_at);
        $this->assertTrue(AssociationCandidate::find($a->id)->qualified_round2);
        $this->assertTrue(AssociationCandidate::find($b->id)->qualified_round2);
        $this->assertFalse(AssociationCandidate::find($c->id)->qualified_round2);
        $this->assertSame('second_round', AssociationElectionResult::where('election_id', $e->id)->where('round', 1)->value('status'));
        $this->assertSame('voting', $e->phase());

        // Au second tour, le candidat non qualifié n'est plus proposé.
        try {
            app(CastBallot::class)($e, $voters[4], [$c->id]);
            $this->fail('Un candidat non qualifié a reçu une voix au second tour.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        // Un électeur qui a déjà voté au premier tour peut voter au second : un bulletin par tour.
        app(CastBallot::class)($e, $voters[4], [$b->id]);
        $this->assertSame(1, AssociationVote::where('round', 2)->count());
    }

    public function test_a_second_round_tie_is_broken_by_seniority_of_membership(): void
    {
        $e = $this->openVoting(48);
        $senior = $this->citizen('Plus ancien');
        $this->member($senior, 'active', now()->subYears(5)->toDateString());
        $junior = $this->citizen('Plus récent');
        $this->member($junior, 'active', now()->subMonths(3)->toDateString());
        $a = AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $junior->id, 'role' => 'president', 'status' => 'approved']);
        $b = AssociationCandidate::create(['election_id' => $e->id, 'citizen_id' => $senior->id, 'role' => 'president', 'status' => 'approved']);
        $x = $this->candidate($e, $this->citizen('X'), 'president');
        $voters = [$this->voter(), $this->voter(), $this->voter(), $this->voter(), $this->voter()];

        // Premier tour : 2 voix pour a, 2 pour b, 1 pour x : personne n'a la majorité.
        app(CastBallot::class)($e, $voters[0], [$a->id]);
        app(CastBallot::class)($e, $voters[1], [$a->id]);
        app(CastBallot::class)($e, $voters[2], [$b->id]);
        app(CastBallot::class)($e, $voters[3], [$b->id]);
        app(CastBallot::class)($e, $voters[4], [$x->id]);
        $this->assertSame('second_round', $this->closeAndProcess($e));

        // Second tour : égalité parfaite 1 contre 1.
        app(CastBallot::class)($e->refresh(), $voters[0], [$a->id]);
        app(CastBallot::class)($e->refresh(), $voters[1], [$b->id]);
        $this->assertSame('proclaimed', $this->closeAndProcess($e));

        // Égalité au second tour : c'est le membre le plus ancien (adhésion de 5 ans) qui est élu.
        $this->assertSame($b->id, AssociationElectionResult::where('election_id', $e->id)->where('round', 2)->value('candidate_id'));
        $this->assertSame($senior->id, AssociationOfficer::where('election_id', $e->id)->value('citizen_id'));
    }
}
