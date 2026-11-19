<?php

namespace App\Actions;

use App\Models\AssociationCandidate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Vérification de la pièce justificative d'une candidature.
 * - Fonctions ordinaires : une vérification suffit.
 * - Président et trésorier : deux vérifications, faites par deux personnes différentes.
 * Dans tous les cas, le vérificateur ne peut pas être le candidat lui-même (même email).
 */
class CheckCandidateDocument
{
    public function __invoke(AssociationCandidate $candidate, User $by): AssociationCandidate
    {
        if ($candidate->election->phase() !== 'candidacy') {
            throw ValidationException::withMessages(['document' => __('Les candidatures ne peuvent plus être modifiées.')]);
        }
        if (! PostConditions::requiresDocument($candidate->election->association, $candidate->role)) {
            throw ValidationException::withMessages(['document' => __('Cette fonction n’exige pas de pièce justificative.')]);
        }
        if (mb_strtolower((string) $by->email) === mb_strtolower((string) $candidate->citizen?->email)) {
            throw ValidationException::withMessages(['document' => __('Le candidat ne peut pas vérifier sa propre pièce.')]);
        }

        if (! PostConditions::requiresDoubleCheck($candidate->role)) {
            $candidate->update(['document_checked_at' => now(), 'document_checked_by' => $by->id]);

            return $candidate;
        }

        // Double contrôle : première vérification, puis une seconde par une autre personne.
        if ($candidate->document_checked_at === null) {
            $candidate->update(['document_checked_at' => now(), 'document_checked_by' => $by->id]);

            return $candidate;
        }
        if ((int) $candidate->document_checked_by === (int) $by->id) {
            throw ValidationException::withMessages(['document' => __('La seconde vérification doit être faite par une autre personne.')]);
        }
        if ($candidate->document_second_checked_at !== null) {
            throw ValidationException::withMessages(['document' => __('Cette pièce a déjà été vérifiée deux fois.')]);
        }

        $candidate->update(['document_second_checked_at' => now(), 'document_second_checked_by' => $by->id]);

        return $candidate;
    }
}
