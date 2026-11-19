<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationElection;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Ouvre un scrutin en ligne : période de candidature jusqu'à l'ouverture du vote, puis vote jusqu'à la fermeture.
 * Le bureau n'est pas saisi : il est proclamé automatiquement à partir des votes, après le dernier tour.
 */
class CreateOnlineElection
{
    public function __invoke(Association $association, int $year, string $opensAt, string $closesAt, ?string $notes = null, int $secondRoundHours = 72): AssociationElection
    {
        $opens = CarbonImmutable::parse($opensAt);
        $closes = CarbonImmutable::parse($closesAt);

        if ($closes->lte($opens)) {
            throw ValidationException::withMessages(['voting_closes_at' => __('La fermeture du vote doit être après son ouverture.')]);
        }
        if ($secondRoundHours < 24 || $secondRoundHours > 336) {
            throw ValidationException::withMessages(['second_round_hours' => __('Le second tour dure entre 24 heures et 14 jours.')]);
        }
        if ($association->elections()->where('year', $year)->exists()) {
            throw ValidationException::withMessages(['year' => __('Une élection est déjà enregistrée pour cette année.')]);
        }

        return AssociationElection::create([
            'association_id' => $association->id,
            'year' => $year,
            'held_on' => $closes->toDateString(),
            'voting_opens_at' => $opens,
            'voting_closes_at' => $closes,
            'second_round_hours' => $secondRoundHours,
            'current_round' => 1,
            'notes' => $notes,
        ]);
    }
}
