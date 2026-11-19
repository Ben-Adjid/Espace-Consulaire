<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Actions\TallyElection;
use App\Actions\VerifyVoteChain;
use App\Models\Association;
use App\Models\AssociationElection;
use App\Models\AssociationOfficer;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Informations publiques de l'association des étudiants : jamais la liste des membres. */
class AssociationController extends Controller
{
    /** Activités par page sur la page publique. */
    public const ACTIVITIES_PER_PAGE = 10;

    /** GET /v1/{consulate}/association/elections/{id}/results : uniquement après proclamation. */
    public function results(ConsulateContext $context, int $id): JsonResponse
    {
        $consulate = $context->require();
        $association = Association::withoutGlobalScopes()->where('consulate_id', $consulate->id)->where('is_public', true)->first();
        $election = $association
            ? AssociationElection::query()->where('association_id', $association->id)->where('id', $id)->whereNotNull('proclaimed_at')->first()
            : null;

        if (! $election) {
            return response()->json(['message' => __('Résultats non publiés.')], 404);
        }

        return response()->json(['data' => [
            'year' => $election->year,
            'proclaimed_at' => $election->proclaimed_at->toIso8601String(),
            'partial' => (bool) $election->is_partial,
            'rounds' => app(TallyElection::class)($election),
            'chain' => app(VerifyVoteChain::class)($election->id),
            'attestation' => \App\Actions\CommissionActions::summary($election),
        ]]);
    }

    /** GET /v1/{consulate}/association?page=N (pagination des activités publiques) */
    public function show(Request $request, ConsulateContext $context): JsonResponse
    {
        $consulate = $context->require();
        $association = Association::withoutGlobalScopes()
            ->where('consulate_id', $consulate->id)
            ->where('is_public', true)
            ->with(['officers', 'activities', 'archives'])
            ->first();

        if (! $association) {
            return response()->json(['message' => __('Aucune association publiée pour ce consulat.')], 404);
        }

        $officers = $association->officers
            ->filter(fn (AssociationOfficer $o) => $o->isCurrent())
            ->values();

        $activities = $association->activities->where('is_public', true)->values();
        $lastPage = max(1, (int) ceil($activities->count() / self::ACTIVITIES_PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $pageItems = $activities->forPage($page, self::ACTIVITIES_PER_PAGE);

        return response()->json(['data' => [
            'name' => $association->name,
            'acronym' => $association->acronym,
            'description' => $association->description,
            'email' => $association->email,
            'phone' => $association->phone,
            'is_recognized' => $association->is_recognized,
            // Bureau en exercice uniquement ; seuls la fonction, le nom, l'établissement et les dates de mandat sont publiés.
            'officers' => $officers->map(fn (AssociationOfficer $o) => [
                'role' => $o->role,
                'role_label' => Association::ROLES[$o->role] ?? $o->role,
                'full_name' => $o->full_name,
                'school' => $o->school,
                'mandate_start' => $o->mandate_start?->toDateString(),
                'mandate_end' => $o->mandate_end?->toDateString(),
            ])->values(),
            // Activités et archives publiées uniquement.
            'activities' => $pageItems->map(fn ($a) => [
                'title' => $a->title,
                'description' => $a->description,
                'starts_on' => $a->starts_on->toDateString(),
                'location' => $a->location,
            ])->values(),
            'activities_page' => [
                'page' => $page,
                'last_page' => $lastPage,
                'total' => $activities->count(),
            ],
            'archives' => $association->archives
                ->where('is_public', true)
                ->map(fn ($a) => [
                    'person_name' => $a->person_name,
                    'title' => $a->title,
                    'summary' => $a->summary,
                ])->values(),
        ]]);
    }
}
