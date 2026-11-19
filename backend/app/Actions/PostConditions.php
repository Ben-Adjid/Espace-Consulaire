<?php

namespace App\Actions;

use App\Models\Association;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\AssociationPostRule;
use App\Models\Citizen;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Conditions d'une fonction, fixées par l'association. Renvoie la liste des conditions non remplies.
 *
 * Règles qui ne dépendent pas de l'association (fixes) :
 * - conflit d'intérêts : un compte du personnel (ou toute personne ayant un compte d'administration)
 *   ne peut pas être candidat au bureau de l'association ;
 * - président et trésorier : pièce justificative vérifiée par DEUX personnes différentes du consulat, dont aucune n'est le candidat.
 *
 * Règles paramétrables (table association_post_rules) :
 * - ancienneté d'adhésion (en mois) ;
 * - statut étudiant ou stagiaire ;
 * - nombre maximal de mandats CONSÉCUTIFS dans la même fonction. Une interruption d'au moins 365 jours remet le compteur à zéro.
 */
class PostConditions
{
    /** Fonctions soumises au double contrôle de la pièce justificative. */
    public const DOUBLE_CHECK_ROLES = ['president', 'treasurer'];

    /** Durée d'interruption qui remet à zéro le compteur de mandats consécutifs. */
    public const RESET_AFTER_DAYS = 365;

    /** @return list<string> conditions non remplies (vide = éligible à cette fonction) */
    public static function unmet(Association $association, Citizen $citizen, string $role): array
    {
        $unmet = [];

        if (self::conflictOfInterest($citizen)) {
            $unmet[] = __('Les comptes du personnel du consulat ne peuvent pas être candidats au bureau.');
        }

        $rule = AssociationPostRule::where('association_id', $association->id)->where('role', $role)->first();
        if (! $rule) {
            return $unmet;
        }

        $member = AssociationMember::where('association_id', $association->id)->where('citizen_id', $citizen->id)->where('status', 'active')->first();

        if ($rule->min_membership_months > 0) {
            $months = $member?->joined_at ? (int) $member->joined_at->diffInMonths(now()) : 0;
            if ($months < $rule->min_membership_months) {
                $unmet[] = __('Adhérer depuis au moins :months mois (vous : :have).', ['months' => $rule->min_membership_months, 'have' => $months]);
            }
        }

        if ($rule->requires_student && ! in_array($member?->category, ['student', 'intern'], true)) {
            $unmet[] = __('Être étudiant(e) ou stagiaire.');
        }

        if ($rule->max_consecutive_terms !== null && self::consecutiveTerms($association, $citizen, $role) >= $rule->max_consecutive_terms) {
            $unmet[] = __('Avoir déjà exercé ce poste :max fois de suite au maximum.', ['max' => $rule->max_consecutive_terms]);
        }

        return $unmet;
    }

    /** Un compte du personnel (ou une personne ayant un compte d'administration) a-t-il le même email que ce citoyen ? */
    public static function conflictOfInterest(Citizen $citizen): bool
    {
        return User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $citizen->email)])->exists();
    }

    /**
     * Nombre de mandats terminés consécutifs dans la même fonction, le plus récent d'abord.
     * Deux mandats se suivent si l'écart entre la fin de l'un et le début de l'autre est inférieur à RESET_AFTER_DAYS.
     */
    public static function consecutiveTerms(Association $association, Citizen $citizen, string $role): int
    {
        $terms = AssociationOfficer::where('association_id', $association->id)
            ->where('citizen_id', $citizen->id)
            ->where('role', $role)
            ->whereNotNull('mandate_end')
            ->orderByDesc('mandate_start')
            ->get();

        $count = 0;
        $nextStart = null;
        foreach ($terms as $term) {
            if ($nextStart === null || CarbonImmutable::parse($term->mandate_end)->diffInDays($nextStart, false) < self::RESET_AFTER_DAYS) {
                $count++;
                $nextStart = CarbonImmutable::parse($term->mandate_start);

                continue;
            }
            break;
        }

        return $count;
    }

    /** La pièce justificative de cette fonction exige-t-elle une vérification ? */
    public static function requiresDocument(Association $association, string $role): bool
    {
        return AssociationPostRule::where('association_id', $association->id)->where('role', $role)->value('requires_document') === true
            || in_array($role, self::DOUBLE_CHECK_ROLES, true);
    }

    /** Cette fonction exige-t-elle la vérification par deux personnes ? */
    public static function requiresDoubleCheck(string $role): bool
    {
        return in_array($role, self::DOUBLE_CHECK_ROLES, true);
    }
}
