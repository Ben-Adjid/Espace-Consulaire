<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\AssociationElectionResult;

/**
 * Résultats publiés d'un scrutin, par tour et par fonction. Ne calcule rien pendant le vote :
 * les chiffres n'existent qu'une fois le tour traité par le système. Ne révèle jamais qui a voté.
 */
class TallyElection
{
    /** @return array<int, array{round: int, roles: list<array{role: string, label: string, status: string, winner: ?string, votes: int, turnout: int}>}> */
    public function __invoke(AssociationElection $election): array
    {
        $rows = AssociationElectionResult::where('election_id', $election->id)
            ->with('candidate.citizen')->orderBy('round')->orderBy('role')->get();

        return $rows->groupBy('round')->map(fn ($group, $round) => [
            'round' => (int) $round,
            'roles' => $group->map(fn (AssociationElectionResult $r) => [
                'role' => $r->role,
                'label' => Association::ROLES[$r->role] ?? $r->role,
                'status' => $r->status,
                'winner' => $r->candidate?->citizen?->full_name,
                'votes' => (int) $r->votes,
                'turnout' => (int) $r->turnout,
            ])->values()->all(),
        ])->values()->all();
    }
}
