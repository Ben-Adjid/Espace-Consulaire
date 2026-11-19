<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationAttestation;
use App\Models\AssociationCommissionMember;
use App\Models\AssociationElection;
use App\Models\AssociationElectionResult;
use App\Models\Citizen;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Commission électorale : trois citoyens hors personnel, qui attestent que le résultat publié correspond au journal des votes.
 * La publication n'attend pas la commission (automatisme), mais le résultat affiche combien de membres l'ont attesté.
 */
class CommissionActions
{
    /** Seuil d'attestations pour considérer le résultat « attesté » par la commission. */
    public const ATTESTATIONS_REQUIRED = 2;

    public static function add(Association $association, Citizen $citizen): AssociationCommissionMember
    {
        if (AssociationCommissionMember::where('association_id', $association->id)->count() >= AssociationCommissionMember::MAX_MEMBERS) {
            throw ValidationException::withMessages(['citizen_id' => __('La commission compte déjà :max membres.', ['max' => AssociationCommissionMember::MAX_MEMBERS])]);
        }
        if (PostConditions::conflictOfInterest($citizen)) {
            throw ValidationException::withMessages(['citizen_id' => __('Une personne du personnel du consulat ne peut pas siéger à la commission électorale.')]);
        }

        return AssociationCommissionMember::firstOrCreate(['association_id' => $association->id, 'citizen_id' => $citizen->id]);
    }

    /** Attestation d'un membre, une fois le résultat publié. Le membre ne peut pas être candidat à ce scrutin. */
    public static function attest(AssociationElection $election, Citizen $citizen): AssociationAttestation
    {
        $isMember = AssociationCommissionMember::where('association_id', $election->association_id)->where('citizen_id', $citizen->id)->exists();
        if (! $isMember) {
            throw ValidationException::withMessages(['attest' => __('Seuls les membres de la commission électorale peuvent attester.')]);
        }
        if (! AssociationElectionResult::where('election_id', $election->id)->exists() || $election->proclaimed_at === null) {
            throw ValidationException::withMessages(['attest' => __('Le résultat n’est pas encore publié.')]);
        }
        if ($election->candidates()->where('citizen_id', $citizen->id)->exists()) {
            throw ValidationException::withMessages(['attest' => __('Un membre de la commission ne peut pas attester un scrutin auquel il est candidat.')]);
        }

        $head = app(VerifyVoteChain::class)($election->id)['head'];
        if ($head === null) {
            throw ValidationException::withMessages(['attest' => __('Aucun vote à attester.')]);
        }

        return AssociationAttestation::firstOrCreate(
            ['election_id' => $election->id, 'citizen_id' => $citizen->id],
            ['chain_head' => $head, 'signed_at' => CarbonImmutable::now()],
        );
    }

    /** @return array{count: int, required: int, attested: bool} */
    public static function summary(AssociationElection $election): array
    {
        $count = AssociationAttestation::where('election_id', $election->id)->count();

        return ['count' => $count, 'required' => self::ATTESTATIONS_REQUIRED, 'attested' => $count >= self::ATTESTATIONS_REQUIRED];
    }
}
