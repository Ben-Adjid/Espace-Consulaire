<?php

namespace App\Actions;

use App\Models\AssociationCandidate;
use Illuminate\Validation\ValidationException;

/**
 * Validation ou refus d'une candidature par le consulat, pendant la période de candidature.
 * Si la fonction exige une pièce, elle doit avoir été vérifiée avant validation.
 */
class ReviewCandidacy
{
    public function __invoke(AssociationCandidate $candidate, string $decision): AssociationCandidate
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => __('Décision inconnue.')]);
        }
        if ($candidate->election->phase() !== 'candidacy') {
            throw ValidationException::withMessages(['decision' => __('Les candidatures ne peuvent plus être modifiées.')]);
        }
        if ($decision === 'approved' && PostConditions::requiresDocument($candidate->election->association, $candidate->role) && $candidate->document_checked_at === null) {
            throw ValidationException::withMessages(['decision' => __('Vérifiez d’abord la pièce justificative de cette fonction.')]);
        }
        if ($decision === 'approved' && PostConditions::requiresDoubleCheck($candidate->role) && $candidate->document_second_checked_at === null) {
            throw ValidationException::withMessages(['decision' => __('Cette fonction exige une seconde vérification de la pièce, par une autre personne.')]);
        }

        $candidate->update(['status' => $decision]);

        return $candidate;
    }
}
