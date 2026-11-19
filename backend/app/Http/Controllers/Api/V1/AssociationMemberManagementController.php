<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BoardJournal;
use App\Actions\LedgerActions;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AssociationMember;
use App\Models\Citizen;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion des adhérents : réservée au président en exercice.
 * Les contacts (email, téléphone) sont enregistrés mais ne sont jamais renvoyés par l'API du bureau.
 */
class AssociationMemberManagementController extends Controller
{
    public function __construct(private ConsulateContext $context)
    {
    }

    /** POST /citizen/association/members */
    public function store(Request $request): JsonResponse
    {
        [$citizen, $association] = $this->presidentOnly($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $data = $this->validated($request, required: true);
        $member = AssociationMember::create($data + ['association_id' => $association->id, 'status' => $data['status'] ?? 'active']);
        BoardJournal::record($association, $citizen, 'member_added', __('Adhérent ajouté : :name', ['name' => $member->full_name]));

        return response()->json(['data' => ['id' => $member->id]], 201);
    }

    /** PATCH /citizen/association/members/{id} : modification, ou désactivation (status = inactive). */
    public function update(Request $request, int $id): JsonResponse
    {
        [$citizen, $association] = $this->presidentOnly($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $member = AssociationMember::where('association_id', $association->id)->find($id);
        if (! $member) {
            return response()->json(['message' => __('Adhérent introuvable.')], 404);
        }

        $member->update($this->validated($request, required: false));
        $action = $member->status === 'inactive' ? 'member_deactivated' : 'member_updated';
        BoardJournal::record($association, $citizen, $action, __('Adhérent modifié : :name', ['name' => $member->full_name]));

        return response()->json(['data' => ['updated' => true]]);
    }

    private function validated(Request $request, bool $required): array
    {
        $r = $required ? 'required' : 'sometimes';

        return $request->validate([
            'full_name' => [$r, 'string', 'max:160'],
            'category' => [$r, 'string', 'in:'.implode(',', array_keys(AssociationMember::CATEGORIES))],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'joined_at' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);
    }

    /** @return array{0: Citizen|JsonResponse, 1: ?Association} */
    private function presidentOnly(Request $request): array
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return [response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401), null];
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return [response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403), null];
        }
        $association = Association::withoutGlobalScopes()->where('consulate_id', $citizen->consulate_id)->first();
        if (! $association || LedgerActions::currentRole($association, $citizen) !== 'president') {
            return [response()->json(['message' => __('Votre fonction ne permet pas cette action.')], 403), null];
        }

        return [$citizen, $association];
    }
}
