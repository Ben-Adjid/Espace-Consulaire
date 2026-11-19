<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationCandidate;
use App\Models\AssociationElection;
use App\Models\Citizen;
use Illuminate\Validation\ValidationException;

/** Candidature d'un membre éligible à une fonction, pendant la période de candidature, si les conditions de la fonction sont remplies. */
class ApplyCandidacy
{
    public function __invoke(AssociationElection $election, Citizen $citizen, string $role): AssociationCandidate
    {
        if ($election->phase() !== 'candidacy') {
            throw ValidationException::withMessages(['role' => __('La période de candidature est terminée.')]);
        }
        if (! array_key_exists($role, Association::ROLES)) {
            throw ValidationException::withMessages(['role' => __('Fonction inconnue.')]);
        }
        if ($election->scope_roles !== null && ! in_array($role, $election->scope_roles, true)) {
            throw ValidationException::withMessages(['role' => __('Cette élection partielle ne concerne pas cette fonction.')]);
        }
        if (! VotingEligibility::canTakePart($citizen, $election)) {
            throw ValidationException::withMessages(['role' => __('Seuls les membres actifs de l’association, avec un email confirmé, peuvent être candidats.')]);
        }
        $unmet = PostConditions::unmet($election->association, $citizen, $role);
        if ($unmet !== []) {
            throw ValidationException::withMessages(['role' => __('Conditions non remplies pour cette fonction : :list', ['list' => implode(' ; ', $unmet)])]);
        }
        if (AssociationCandidate::where('election_id', $election->id)->where('citizen_id', $citizen->id)->exists()) {
            throw ValidationException::withMessages(['role' => __('Vous êtes déjà candidat à cette élection.')]);
        }

        return AssociationCandidate::create([
            'election_id' => $election->id,
            'citizen_id' => $citizen->id,
            'role' => $role,
            'status' => 'pending',
        ]);
    }
}
