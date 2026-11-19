<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationElection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enregistre une élection faite hors ligne : crée l'élection et installe le nouveau bureau.
 * Une seule élection par année et par association.
 */
class RecordElection
{
    /**
     * @param  array<int, array{role: string, full_name: string, school?: ?string, citizen_id?: ?int}>  $officers
     */
    public function __invoke(Association $association, int $year, string $heldOn, array $officers, ?string $notes = null): AssociationElection
    {
        if ($association->elections()->where('year', $year)->exists()) {
            throw ValidationException::withMessages(['year' => __('Une élection est déjà enregistrée pour cette année.')]);
        }

        $election = DB::transaction(fn () => AssociationElection::create([
            'association_id' => $association->id,
            'year' => $year,
            'held_on' => $heldOn,
            'notes' => $notes,
        ]));

        app(InstallBoard::class)($association, $election, $heldOn, $officers);

        return $election;
    }
}
