<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;

/**
 * Fin d'adhésion liée au mandat : quand le mandat d'un titulaire est terminé et qu'il n'a aucun autre
 * mandat en cours dans l'association, son adhésion au registre passe à « inactif ».
 * Une personne qui n'a jamais exercé de mandat garde son adhésion : seule la fin de mandat la termine.
 * Réactivation : manuelle, par l'admin (un nouveau mandat ne réactive pas automatiquement).
 */
class EndMemberships
{
    /** Retourne le nombre d'adhésions terminées. */
    public function __invoke(?Association $association = null): int
    {
        $today = now()->toDateString();

        $members = AssociationMember::query()
            ->where('status', 'active')
            ->whereNotNull('citizen_id')
            ->when($association, fn ($q) => $q->where('association_id', $association->id))
            ->get();

        $ended = 0;
        foreach ($members as $member) {
            $mandates = AssociationOfficer::query()
                ->where('association_id', $member->association_id)
                ->where('citizen_id', $member->citizen_id)
                ->get();

            if ($mandates->isEmpty()) {
                continue;
            }

            $hasCurrent = $mandates->contains(fn (AssociationOfficer $o) => $o->mandate_end === null || $o->mandate_end->toDateString() >= $today);
            $hasEnded = $mandates->contains(fn (AssociationOfficer $o) => $o->mandate_end !== null && $o->mandate_end->toDateString() < $today);

            if (! $hasCurrent && $hasEnded) {
                $member->update(['status' => 'inactive']);
                $ended++;
            }
        }

        return $ended;
    }
}
