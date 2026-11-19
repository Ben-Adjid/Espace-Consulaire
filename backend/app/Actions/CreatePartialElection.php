<?php

namespace App\Actions;

use App\Models\AssociationElection;
use Carbon\CarbonImmutable;

/**
 * Élection partielle pour les seuls postes vacants : candidatures pendant 7 jours, puis vote pendant 7 jours.
 * Elle ne remplace pas le bureau : elle ne ferme que les mandats des fonctions concernées (voir InstallBoard).
 * Si un poste reste vacant après cette élection partielle, il est signalé, sans relance (décision à prendre).
 */
class CreatePartialElection
{
    public const CANDIDACY_DAYS = 7;

    public const VOTING_DAYS = 7;

    /** @param  list<string>  $roles  @param  string  $electorate  members | board */
    public function __invoke(AssociationElection $parent, array $roles, string $electorate = 'members'): AssociationElection
    {
        $opens = CarbonImmutable::now()->addDays(self::CANDIDACY_DAYS);
        $closes = $opens->addDays(self::VOTING_DAYS);

        return AssociationElection::create([
            'association_id' => $parent->association_id,
            'year' => $parent->year,
            'held_on' => $closes->toDateString(),
            'voting_opens_at' => $opens,
            'voting_closes_at' => $closes,
            'second_round_hours' => $parent->second_round_hours,
            'current_round' => 1,
            'is_partial' => true,
            'electorate' => $electorate,
            'scope_roles' => array_values($roles),
            'notes' => $electorate === 'board'
                ? __('Vote du bureau pour les postes restés vacants : :roles', ['roles' => implode(', ', $roles)])
                : __('Élection partielle pour les postes vacants : :roles', ['roles' => implode(', ', $roles)]),
        ]);
    }
}
