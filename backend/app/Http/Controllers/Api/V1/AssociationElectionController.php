<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ApplyCandidacy;
use App\Actions\CastBallot;
use App\Actions\CommissionActions;
use App\Actions\PostConditions;
use App\Actions\TallyElection;
use App\Actions\VerifyVoteChain;
use App\Actions\VotingEligibility;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AssociationBallot;
use App\Models\AssociationCandidate;
use App\Models\AssociationElection;
use App\Models\AssociationElectionResult;
use App\Models\Citizen;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Élections en ligne, côté citoyen : voir les scrutins de son association, candidater, retirer sa candidature,
 * voter (un bulletin par tour), vérifier son reçu, consulter les résultats publiés.
 * Jamais de totaux pendant le vote, jamais le nom des électeurs.
 */
class AssociationElectionController extends Controller
{
    public function __construct(private ConsulateContext $context)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $association = Association::withoutGlobalScopes()->where('consulate_id', $this->context->require()->id)->first();
        if (! $association) {
            return response()->json(['data' => [], 'meta' => ['eligible' => false]]);
        }

        $elections = $association->elections()->whereNotNull('voting_opens_at')
            ->orderByDesc('year')->limit(3)->get();

        $data = $elections->map(fn (AssociationElection $e) => $this->view($e, $citizen))->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'eligible' => VotingEligibility::canTakePart($citizen, $elections->first() ?? new AssociationElection(['association_id' => $association->id])),
                'roles' => Association::ROLES,
                'conditions' => $this->conditionsFor($association, $citizen),
                'is_commission' => \App\Models\AssociationCommissionMember::where('association_id', $association->id)->where('citizen_id', $citizen->id)->exists(),
            ],
        ]);
    }

    public function candidacy(Request $request, int $id): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $election = $this->electionFor($citizen, $id);
        if (! $election instanceof AssociationElection) {
            return $election;
        }

        $data = $request->validate([
            'role' => ['required', 'string', 'max:30'],
            // Déclaration sur l'honneur obligatoire : aucun lien familial avec le personnel du consulat.
            'declare_no_family_link' => ['required', 'accepted'],
        ]);
        $candidate = app(ApplyCandidacy::class)($election, $citizen, $data['role']);
        $candidate->update(['family_link_declared_at' => now()]);

        return response()->json(['data' => $this->view($election->refresh(), $citizen)]);
    }

    public function withdraw(Request $request, int $id): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $election = $this->electionFor($citizen, $id);
        if (! $election instanceof AssociationElection) {
            return $election;
        }
        if ($election->phase() !== 'candidacy') {
            return response()->json(['message' => __('La période de candidature est terminée.')], 422);
        }

        AssociationCandidate::where('election_id', $election->id)->where('citizen_id', $citizen->id)->where('status', 'pending')->delete();

        return response()->json(['data' => $this->view($election->refresh(), $citizen)]);
    }

    public function vote(Request $request, int $id): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $election = $this->electionFor($citizen, $id);
        if (! $election instanceof AssociationElection) {
            return $election;
        }

        $data = $request->validate(['candidate_ids' => ['required', 'array', 'min:1', 'max:20'], 'candidate_ids.*' => ['integer']]);
        $receipt = app(CastBallot::class)($election, $citizen, $data['candidate_ids']);

        // Le code de reçu n'est affiché qu'une seule fois : seule son empreinte est conservée.
        return response()->json(['data' => $this->view($election->refresh(), $citizen), 'receipt' => $receipt]);
    }

    /** Un membre de la commission électorale atteste que le résultat correspond au journal. */
    public function attest(Request $request, int $id): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $election = $this->electionFor($citizen, $id);
        if (! $election instanceof AssociationElection) {
            return $election;
        }

        CommissionActions::attest($election, $citizen);

        return response()->json(['data' => $this->view($election->refresh(), $citizen)]);
    }

    public function verifyReceipt(Request $request, int $id): JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $election = $this->electionFor($citizen, $id);
        if (! $election instanceof AssociationElection) {
            return $election;
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $valid = AssociationBallot::where('election_id', $election->id)
            ->where('citizen_id', $citizen->id)
            ->where('receipt_hash', hash('sha256', strtoupper(trim($data['code']))))
            ->exists();

        return response()->json(['valid' => $valid]);
    }

    /** @return array<string, mixed> */
    private function view(AssociationElection $e, Citizen $citizen): array
    {
        $phase = $e->phase();
        $round = (int) $e->current_round;
        $published = AssociationElectionResult::where('election_id', $e->id)->exists();

        $openRoles = $round > 1
            ? AssociationElectionResult::where('election_id', $e->id)->where('round', 1)->where('status', 'second_round')->pluck('role')->all()
            : null;

        $candidates = AssociationCandidate::where('election_id', $e->id)->where('status', 'approved')
            ->when($round > 1, fn ($q) => $q->where('qualified_round2', true)->whereIn('role', $openRoles ?? []))
            ->with('citizen')->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->map(fn (AssociationCandidate $c) => ['id' => $c->id, 'name' => $c->citizen?->full_name])->values());

        $mine = AssociationCandidate::where('citizen_id', $citizen->id)->where('election_id', $e->id)->first();
        $votedThisRound = AssociationBallot::where('citizen_id', $citizen->id)->where('election_id', $e->id)->where('round', $round)->exists();
        $canVote = VotingEligibility::canVote($citizen, $e);

        return [
            'id' => $e->id,
            'year' => $e->year,
            'phase' => $phase,
            'round' => $round,
            'opens_at' => $e->voting_opens_at?->toIso8601String(),
            'closes_at' => $e->voting_closes_at?->toIso8601String(),
            'candidates' => $candidates,
            'my_candidacy' => $mine ? ['role' => $mine->role, 'status' => $mine->status] : null,
            'has_voted' => $votedThisRound,
            'can_candidate' => VotingEligibility::canTakePart($citizen, $e) && $phase === 'candidacy' && $mine === null,
            'can_withdraw' => $phase === 'candidacy' && $mine?->status === 'pending',
            'can_vote' => $canVote && $phase === 'voting' && ! $votedThisRound && $candidates->isNotEmpty(),
            // Résultats uniquement une fois traités par le système (jamais pendant le vote).
            'results' => $published ? app(TallyElection::class)($e) : null,
            'attestation' => $published ? CommissionActions::summary($e) : null,
            'is_partial' => (bool) $e->is_partial,
            // Élection partielle : fonctions vacantes couvertes, affichées pour distinguer le scrutin de l'élection principale.
            'scope_roles' => $e->is_partial ? array_values((array) $e->scope_roles) : null,
            'chain' => $published ? app(VerifyVoteChain::class)($e->id) : null,
        ];
    }

    /** Conditions de chaque fonction, et celles que ce citoyen ne remplit pas encore. */
    private function conditionsFor(Association $association, Citizen $citizen): array
    {
        return collect(Association::ROLES)->map(fn ($label, $role) => [
            'role' => $role,
            'label' => $label,
            'unmet' => PostConditions::unmet($association, $citizen, $role),
            'requires_document' => PostConditions::requiresDocument($association, $role),
        ])->values()->all();
    }

    private function electionFor(Citizen $citizen, int $id): AssociationElection|JsonResponse
    {
        $association = Association::withoutGlobalScopes()->where('consulate_id', $citizen->consulate_id)->first();
        $election = $association
            ? AssociationElection::query()->where('association_id', $association->id)->where('id', $id)->whereNotNull('voting_opens_at')->first()
            : null;

        return $election ?? response()->json(['message' => __('Élection introuvable.')], 404);
    }

    private function citizen(Request $request): Citizen|JsonResponse
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401);
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }

        return $citizen;
    }
}
