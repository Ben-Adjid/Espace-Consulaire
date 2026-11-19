<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationLedgerEntry;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use Illuminate\Validation\ValidationException;

/**
 * Registre financier de l'association. Règles :
 * - seul le TRÉSORIER en exercice enregistre les cotisations ;
 * - toute membre du bureau en exercice peut PROPOSER une dépense ;
 * - une dépense n'est effective qu'après approbation d'un AUTRE membre du bureau ;
 * - aucune dépense ne peut rendre le solde négatif ;
 * - le consulat ne touche pas à l'argent : le personnel n'a pas de rôle ici.
 * Une écriture approuvée est figée (voir AssociationLedgerEntry).
 */
class LedgerActions
{
    /** Le citoyen exerce-t-il une fonction actuelle dans le bureau ? Renvoie la fonction, ou null. */
    public static function currentRole(Association $association, Citizen $citizen): ?string
    {
        return AssociationOfficer::where('association_id', $association->id)
            ->where('citizen_id', $citizen->id)
            ->get()
            ->first(fn (AssociationOfficer $o) => $o->isCurrent())?->role;
    }

    public static function balance(Association $association): int
    {
        $in = AssociationLedgerEntry::where('association_id', $association->id)->where('kind', 'contribution')->where('status', 'approved')->sum('amount');
        $out = AssociationLedgerEntry::where('association_id', $association->id)->where('kind', 'expense')->where('status', 'approved')->sum('amount');

        return (int) $in - (int) $out;
    }

    public static function recordContribution(Association $association, Citizen $treasurer, int $amount, string $label, string $date): AssociationLedgerEntry
    {
        self::requireAmount($amount);
        if (self::currentRole($association, $treasurer) !== 'treasurer') {
            throw ValidationException::withMessages(['ledger' => __('Seul le trésorier en exercice peut enregistrer une cotisation.')]);
        }

        $entry = AssociationLedgerEntry::create([
            'association_id' => $association->id,
            'kind' => 'contribution',
            'amount' => $amount,
            'label' => $label,
            'occurred_on' => $date,
            'proposed_by' => $treasurer->id,
            'approved_by' => $treasurer->id,
            'approved_at' => now(),
            'status' => 'approved',
        ]);
        BoardJournal::record($association, $treasurer, 'contribution', __('Cotisation de :amount FCFA : :label', ['amount' => $amount, 'label' => $label]));

        return $entry;
    }

    public static function proposeExpense(Association $association, Citizen $proposer, int $amount, string $label, string $date): AssociationLedgerEntry
    {
        self::requireAmount($amount);
        if (self::currentRole($association, $proposer) === null) {
            throw ValidationException::withMessages(['ledger' => __('Seuls les membres du bureau en exercice peuvent proposer une dépense.')]);
        }

        return AssociationLedgerEntry::create([
            'association_id' => $association->id,
            'kind' => 'expense',
            'amount' => $amount,
            'label' => $label,
            'occurred_on' => $date,
            'proposed_by' => $proposer->id,
            'status' => 'pending',
        ]);
    }

    public static function approveExpense(AssociationLedgerEntry $entry, Citizen $approver): AssociationLedgerEntry
    {
        if ($entry->kind !== 'expense' || $entry->status !== 'pending') {
            throw ValidationException::withMessages(['ledger' => __('Cette écriture n’est pas en attente d’approbation.')]);
        }
        if ((int) $entry->proposed_by === (int) $approver->id) {
            throw ValidationException::withMessages(['ledger' => __('La dépense doit être approuvée par une autre personne que celle qui la propose.')]);
        }
        if (self::currentRole($entry->association, $approver) === null) {
            throw ValidationException::withMessages(['ledger' => __('Seuls les membres du bureau en exercice peuvent approuver une dépense.')]);
        }
        if ($entry->amount > self::balance($entry->association)) {
            throw ValidationException::withMessages(['ledger' => __('Solde insuffisant pour cette dépense.')]);
        }

        $entry->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()]);
        BoardJournal::record($entry->association, $approver, 'expense_approved', __('Dépense approuvée de :amount FCFA : :label', ['amount' => $entry->amount, 'label' => $entry->label]));

        return $entry;
    }

    private static function requireAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => __('Le montant doit être supérieur à zéro.')]);
        }
    }
}
