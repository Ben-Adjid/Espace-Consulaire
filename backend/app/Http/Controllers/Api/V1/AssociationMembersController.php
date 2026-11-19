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
 * Registre des adhérents, en lecture, pour les membres du bureau en exercice uniquement.
 * Nom, catégorie et statut. Jamais de contact (email, téléphone) ni de notes.
 */
class AssociationMembersController extends Controller
{
    public function __construct(private ConsulateContext $context)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401);
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }

        $association = Association::withoutGlobalScopes()->where('consulate_id', $citizen->consulate_id)->first();
        if (! $association || LedgerActions::currentRole($association, $citizen) === null) {
            return response()->json(['message' => __('Réservé aux membres du bureau en exercice.')], 403);
        }

        $members = AssociationMember::where('association_id', $association->id)
            ->orderBy('full_name')->get()
            ->map(fn (AssociationMember $m) => [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'category' => $m->category,
                'category_label' => __(AssociationMember::CATEGORIES[$m->category] ?? $m->category),
                'status' => $m->status,
                'status_label' => __(AssociationMember::STATUSES[$m->status] ?? $m->status),
                'joined_at' => $m->joined_at?->toDateString(),
            ])->values();

        return response()->json(['data' => $members, 'meta' => ['total' => $members->count()]]);
    }

    /**
     * GET /citizen/association/members/export.csv : registre en CSV (nom, catégorie, statut, date d'adhésion).
     * Jamais de contact. Chaque export est inscrit au journal du bureau.
     */
    public function export(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401);
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }

        $association = Association::withoutGlobalScopes()->where('consulate_id', $citizen->consulate_id)->first();
        if (! $association || LedgerActions::currentRole($association, $citizen) === null) {
            return response()->json(['message' => __('Réservé aux membres du bureau en exercice.')], 403);
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [__('Nom'), __('Catégorie'), __('Statut'), __('Date d’adhésion')], ';');
        AssociationMember::where('association_id', $association->id)->orderBy('full_name')->get()
            ->each(fn (AssociationMember $m) => fputcsv($handle, [
                $m->full_name,
                __(AssociationMember::CATEGORIES[$m->category] ?? $m->category),
                __(AssociationMember::STATUSES[$m->status] ?? $m->status),
                $m->joined_at?->toDateString(),
            ], ';'));
        rewind($handle);
        $csv = "\xEF\xBB\xBF".stream_get_contents($handle);
        fclose($handle);

        BoardJournal::record($association, $citizen, 'export', __('Export du registre des adhérents'));

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="registre-adherents.csv"',
        ]);
    }
}
