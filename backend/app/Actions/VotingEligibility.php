<?php

namespace App\Actions;

use App\Models\AssociationElection;
use App\Models\AssociationElectorate;
use App\Models\AssociationMember;
use App\Models\Citizen;

/**
 * Qui peut être candidat ou voter.
 * - Candidature : compte actif, email confirmé, adhésion ACTIVE au registre (et les conditions de la fonction).
 * - Vote : être sur la liste des électeurs figée à l'ouverture du vote. Une adhésion modifiée après ne change rien.
 */
class VotingEligibility
{
    public static function canTakePart(Citizen $citizen, AssociationElection $election): bool
    {
        if (! $citizen->isActive() || $citizen->email_verified_at === null) {
            return false;
        }

        return AssociationMember::query()
            ->where('association_id', $election->association_id)
            ->where('citizen_id', $citizen->id)
            ->where('status', 'active')
            ->exists();
    }

    public static function canVote(Citizen $citizen, AssociationElection $election): bool
    {
        if (! $citizen->isActive() || $citizen->email_verified_at === null) {
            return false;
        }

        if ($election->electorate_frozen_at !== null) {
            return AssociationElectorate::where('election_id', $election->id)->where('citizen_id', $citizen->id)->exists();
        }

        return self::canTakePart($citizen, $election);
    }
}
