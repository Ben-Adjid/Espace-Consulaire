<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BoardJournal;
use App\Actions\BoardPermissions;
use App\Actions\LedgerActions;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AssociationActivity;
use App\Models\AssociationBoardLog;
use App\Models\AssociationElection;
use App\Models\AssociationLedgerEntry;
use App\Models\AssociationMember;
use App\Models\AssociationOfficer;
use App\Models\Citizen;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Espace du bureau : tableau de bord, publication d'activités, informations publiques. Chaque action vérifie la fonction. */
class AssociationBoardController extends Controller
{
    public function __construct(private ConsulateContext $context)
    {
    }

    /** GET /citizen/association/board : tableau de bord, selon la fonction. */
    public function dashboard(Request $request): JsonResponse
    {
        [$citizen, $association, $role] = $this->actor($request, BoardPermissions::DASHBOARD);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $mandatesEnding = AssociationOfficer::where('association_id', $association->id)
            ->whereNotNull('mandate_end')->whereBetween('mandate_end', [now()->toDateString(), now()->addDays(60)->toDateString()])
            ->get(['role', 'full_name', 'mandate_end']);

        return response()->json(['data' => [
            'role' => $role,
            'role_label' => \App\Models\Association::ROLES[$role] ?? $role,
            'members_active' => AssociationMember::where('association_id', $association->id)->where('status', 'active')->count(),
            'balance' => LedgerActions::balance($association),
            'expenses_to_approve' => AssociationLedgerEntry::where('association_id', $association->id)->where('status', 'pending')->count(),
            'open_elections' => AssociationElection::where('association_id', $association->id)->whereNotNull('voting_opens_at')
                ->whereNull('proclaimed_at')->count(),
            'mandates_ending_soon' => $mandatesEnding->map(fn ($o) => ['role' => $o->role, 'full_name' => $o->full_name, 'mandate_end' => $o->mandate_end->toDateString()])->values(),
        ]]);
    }

    /** POST /citizen/association/activities : publication d'une activité publique. */
    public function publishActivity(Request $request): JsonResponse
    {
        [$citizen, $association] = $this->actor($request, BoardPermissions::PUBLISH_ACTIVITY);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_on' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:160'],
        ]);
        $activity = AssociationActivity::create($data + ['association_id' => $association->id, 'is_public' => true]);
        BoardJournal::record($association, $citizen, 'activity', __('Activité publiée : :title', ['title' => $data['title']]));

        return response()->json(['data' => ['id' => $activity->id]], 201);
    }

    /** PATCH /citizen/association/info : description et contacts publics. */
    public function updatePublicInfo(Request $request): JsonResponse
    {
        [$citizen, $association] = $this->actor($request, BoardPermissions::EDIT_PUBLIC_INFO);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);
        $association->update($data);
        BoardJournal::record($association, $citizen, 'info', __('Informations publiques modifiées'));

        return response()->json(['data' => ['updated' => true]]);
    }

    /** GET /citizen/association/journal : journal des actions du bureau, plus récent d'abord. */
    public function journal(Request $request): JsonResponse
    {
        [$citizen, $association] = $this->actor($request, BoardPermissions::DASHBOARD);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $entries = AssociationBoardLog::where('association_id', $association->id)
            ->with('citizen:id,full_name')->latest('created_at')->latest('id')->limit(200)->get()
            ->map(fn (AssociationBoardLog $l) => [
                'at' => $l->created_at?->toIso8601String(),
                'by' => $l->citizen?->full_name ?? __('Compte supprimé'),
                'action' => $l->action,
                'label' => $l->label,
            ])->values();

        return response()->json(['data' => $entries]);
    }

    /** @return array{0: Citizen|JsonResponse, 1: ?Association, 2: ?string} */
    private function actor(Request $request, array $allowedRoles): array
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return [response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401), null, null];
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return [response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403), null, null];
        }
        $association = Association::withoutGlobalScopes()->where('consulate_id', $citizen->consulate_id)->first();
        $role = $association ? LedgerActions::currentRole($association, $citizen) : null;
        if (! $association || ! in_array($role, $allowedRoles, true)) {
            return [response()->json(['message' => __('Votre fonction ne permet pas cette action.')], 403), null, null];
        }

        return [$citizen, $association, $role];
    }
}
