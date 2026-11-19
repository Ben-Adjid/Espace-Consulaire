<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\LedgerActions;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AssociationLedgerEntry;
use App\Models\Citizen;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registre financier : publication anonymisée (totaux et dernières écritures, sans noms), et actions du bureau.
 */
class AssociationLedgerController extends Controller
{
    public function __construct(private ConsulateContext $context)
    {
    }

    /** GET /v1/{consulate}/association/ledger : solde et dernières écritures, publics, sans aucun nom. */
    public function show(ConsulateContext $context): JsonResponse
    {
        $association = $this->association($context->require()->id);
        if (! $association) {
            return response()->json(['message' => __('Aucune association publiée pour ce consulat.')], 404);
        }

        $entries = AssociationLedgerEntry::where('association_id', $association->id)->where('status', 'approved')
            ->orderByDesc('occurred_on')->orderByDesc('id')->limit(20)->get()
            ->map(fn (AssociationLedgerEntry $e) => [
                'kind' => $e->kind,
                'kind_label' => __(AssociationLedgerEntry::KINDS[$e->kind] ?? $e->kind),
                'amount' => $e->amount,
                'label' => $e->label,
                'occurred_on' => $e->occurred_on->toDateString(),
            ])->values();

        return response()->json(['data' => [
            'balance' => LedgerActions::balance($association),
            'currency' => 'FCFA',
            'entries' => $entries,
        ]]);
    }

    /**
     * GET /citizen/association/ledger : vue interne. Visible par les membres, avec les noms des personnes qui ont
     * proposé ou approuvé. Le citoyen voit ses droits : rôle, et ce qu'il peut faire sur chaque écriture.
     */
    public function mine(Request $request): JsonResponse
    {
        [$citizen, $association] = $this->actor($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $role = LedgerActions::currentRole($association, $citizen);
        $entries = AssociationLedgerEntry::where('association_id', $association->id)
            ->with(['proposer', 'approver'])
            ->orderByDesc('occurred_on')->orderByDesc('id')->limit(50)->get()
            ->map(fn (AssociationLedgerEntry $e) => [
                'id' => $e->id,
                'kind' => $e->kind,
                'kind_label' => __(AssociationLedgerEntry::KINDS[$e->kind] ?? $e->kind),
                'amount' => $e->amount,
                'label' => $e->label,
                'occurred_on' => $e->occurred_on->toDateString(),
                'status' => $e->status,
                'proposed_by' => $e->proposer?->full_name,
                'approved_by' => $e->approver?->full_name,
                'can_approve' => $role !== null && $e->kind === 'expense' && $e->status === 'pending'
                    && (int) $e->proposed_by !== (int) $citizen->id,
            ])->values();

        return response()->json(['data' => [
            'role' => $role,
            'balance' => LedgerActions::balance($association),
            'currency' => 'FCFA',
            'can_record_contribution' => $role === 'treasurer',
            'can_propose_expense' => $role !== null,
            'entries' => $entries,
        ]]);
    }

    /** POST /citizen/association/ledger/contribution : cotisation saisie par le trésorier. */
    public function contribution(Request $request): JsonResponse
    {
        [$citizen, $association] = $this->actor($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'label' => ['required', 'string', 'max:190'],
            'occurred_on' => ['required', 'date'],
        ]);
        $entry = LedgerActions::recordContribution($association, $citizen, $data['amount'], $data['label'], $data['occurred_on']);

        return response()->json(['data' => ['id' => $entry->id, 'status' => $entry->status]], 201);
    }

    /** POST /citizen/association/ledger/expense : proposition de dépense par un membre du bureau. */
    public function proposeExpense(Request $request): JsonResponse
    {
        [$citizen, $association] = $this->actor($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'label' => ['required', 'string', 'max:190'],
            'occurred_on' => ['required', 'date'],
        ]);
        $entry = LedgerActions::proposeExpense($association, $citizen, $data['amount'], $data['label'], $data['occurred_on']);

        return response()->json(['data' => ['id' => $entry->id, 'status' => $entry->status]], 201);
    }

    /** POST /citizen/association/ledger/{id}/approve : approbation par un autre membre du bureau. */
    public function approve(Request $request, int $id): JsonResponse
    {
        [$citizen, $association] = $this->actor($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $entry = AssociationLedgerEntry::where('association_id', $association->id)->whereKey($id)->first();
        if (! $entry) {
            return response()->json(['message' => __('Écriture introuvable.')], 404);
        }

        $entry = LedgerActions::approveExpense($entry, $citizen);

        return response()->json(['data' => ['id' => $entry->id, 'status' => $entry->status]]);
    }

    private function association(int $consulateId): ?Association
    {
        return Association::withoutGlobalScopes()->where('consulate_id', $consulateId)->where('is_public', true)->first();
    }

    /** @return array{0: Citizen|JsonResponse, 1: ?Association} */
    private function actor(Request $request): array
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return [response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401), null];
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return [response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403), null];
        }
        $association = Association::withoutGlobalScopes()->where('consulate_id', $citizen->consulate_id)->first();
        if (! $association) {
            return [response()->json(['message' => __('Aucune association pour ce consulat.')], 404), null];
        }

        return [$citizen, $association];
    }
}
