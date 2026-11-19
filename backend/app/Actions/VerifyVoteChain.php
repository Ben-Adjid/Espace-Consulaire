<?php

namespace App\Actions;

use App\Models\AssociationVoteChain;

/**
 * Vérifie le journal des votes : chaque entrée doit contenir l'empreinte exacte de la précédente.
 * Tout n'importe qui peut relancer ce calcul à partir des résultats publiés et détecter une altération.
 */
class VerifyVoteChain
{
    public static function hashOf(string $prev, int $electionId, int $seq, int $round, string $sortedCandidateIds): string
    {
        return hash('sha256', implode('|', [$prev, $electionId, $seq, $round, $sortedCandidateIds]));
    }

    /** @return array{valid: bool, length: int, head: ?string} */
    public function __invoke(int $electionId): array
    {
        $entries = AssociationVoteChain::where('election_id', $electionId)->orderBy('seq')->get();
        $prev = str_repeat('0', 64);
        $valid = true;

        foreach ($entries as $index => $entry) {
            $expectedSeq = $index + 1;
            $recomputed = self::hashOf($prev, $electionId, (int) $entry->seq, (int) $entry->round, (string) $entry->candidate_ids);
            if ($entry->seq !== $expectedSeq || $entry->prev_hash !== $prev || $entry->hash !== $recomputed) {
                $valid = false;
                break;
            }
            $prev = $entry->hash;
        }

        return ['valid' => $valid, 'length' => $entries->count(), 'head' => $entries->last()?->hash];
    }
}
