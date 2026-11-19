<?php

namespace App\Actions;

use App\Models\AssociationBallot;
use App\Models\AssociationCandidate;
use App\Models\AssociationElection;
use App\Models\AssociationElectionResult;
use App\Models\AssociationVote;
use App\Models\AssociationVoteChain;
use App\Models\Citizen;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Vote en ligne, à un tour donné.
 * - Un seul bulletin par électeur et par tour ; au plus un candidat par fonction.
 * - Au second tour, seuls les candidats qualifiés des fonctions non encore pourvues sont proposés.
 * - Les bulletins (qui a voté) et les votes (pour qui) sont séparés ; chaque vote est ajouté au journal chaîné.
 * - Le électeur reçoit un reçu (code) : seule son empreinte est conservée, il prouve que son bulletin existe.
 *
 * @return string le code reçu, affiché une seule fois
 */
class CastBallot
{
    /** @param  array<int, int>  $candidateIds */
    public function __invoke(AssociationElection $election, Citizen $citizen, array $candidateIds): string
    {
        if ($election->phase() !== 'voting') {
            throw ValidationException::withMessages(['candidates' => __('Le vote n’est pas ouvert.')]);
        }
        if (! VotingEligibility::canVote($citizen, $election)) {
            throw ValidationException::withMessages(['candidates' => __('Seuls les électeurs inscrits sur la liste du vote peuvent voter.')]);
        }

        $round = max(1, (int) $election->current_round);
        $candidateIds = array_values(array_unique(array_map('intval', $candidateIds)));
        if ($candidateIds === []) {
            throw ValidationException::withMessages(['candidates' => __('Choisissez au moins un candidat.')]);
        }

        $query = AssociationCandidate::where('election_id', $election->id)->where('status', 'approved')->whereIn('id', $candidateIds);
        if ($round > 1) {
            $open = AssociationElectionResult::where('election_id', $election->id)->where('round', 1)
                ->where('status', 'second_round')->pluck('role')->all();
            $query->where('qualified_round2', true)->whereIn('role', $open);
        }
        $candidates = $query->get();
        if ($candidates->count() !== count($candidateIds)) {
            throw ValidationException::withMessages(['candidates' => __('Un candidat choisi n’est pas valide pour ce tour.')]);
        }
        if ($candidates->pluck('role')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['candidates' => __('Un seul candidat par fonction.')]);
        }

        $code = Str::upper(Str::random(12));
        $sorted = collect($candidateIds)->sort()->implode(',');

        try {
            DB::transaction(function () use ($election, $citizen, $candidates, $round, $code, $sorted) {
                // Verrou sur l'élection : les entrées du journal sont ainsi écrites une à une, sans trou ni doublon.
                AssociationElection::whereKey($election->id)->lockForUpdate()->first();

                AssociationBallot::create([
                    'election_id' => $election->id,
                    'citizen_id' => $citizen->id,
                    'round' => $round,
                    'cast_at' => now(),
                    'receipt_hash' => hash('sha256', $code),
                ]);

                $last = AssociationVoteChain::where('election_id', $election->id)->orderByDesc('seq')->first();
                $seq = ($last?->seq ?? 0) + 1;
                $prev = $last?->hash ?? str_repeat('0', 64);
                AssociationVoteChain::create([
                    'election_id' => $election->id,
                    'seq' => $seq,
                    'round' => $round,
                    'candidate_ids' => $sorted,
                    'prev_hash' => $prev,
                    'hash' => VerifyVoteChain::hashOf($prev, $election->id, $seq, $round, $sorted),
                    'created_at' => now(),
                ]);

                foreach ($candidates as $candidate) {
                    AssociationVote::create(['election_id' => $election->id, 'candidate_id' => $candidate->id, 'round' => $round]);
                }
            });
        } catch (QueryException) {
            // Clé unique (élection, électeur, tour) : un second bulletin au même tour est refusé.
            throw ValidationException::withMessages(['candidates' => __('Vous avez déjà voté.')]);
        }

        return $code;
    }
}
