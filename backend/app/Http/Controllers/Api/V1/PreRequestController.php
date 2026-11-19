<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\PreRequest;
use App\Models\RequestDocument;
use App\Models\Service;
use App\Services\ConsulateContext;
use App\Services\DocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/** Pré-demande en ligne du citoyen connecté : brouillon, pièces, soumission. */
class PreRequestController extends Controller
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

        $items = PreRequest::withoutGlobalScopes()->where('citizen_id', $citizen->id)
            ->with(['service', 'documents'])->latest()->get()
            ->map(fn (PreRequest $p) => $this->view($p));

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $citizen = $this->citizen($request, active: true);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        $data = $request->validate(['service' => ['required', 'string', 'max:120']]);
        $service = Service::withoutGlobalScopes()->where('consulate_id', $citizen->consulate_id)
            ->where('slug', $data['service'])->where('is_active', true)->first();
        if (! $service) {
            return response()->json(['message' => __('Service introuvable.')], 422);
        }

        $pre = PreRequest::withoutGlobalScopes()->create([
            'consulate_id' => $citizen->consulate_id,
            'citizen_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => 'draft',
            'data' => [],
        ]);

        return response()->json(['data' => $this->view($pre->load(['service', 'documents']))], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pre = $this->owned($request, $id);
        if (! $pre instanceof PreRequest) {
            return $pre;
        }

        return response()->json(['data' => $this->view($pre->load(['service', 'documents']))]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pre = $this->owned($request, $id);
        if (! $pre instanceof PreRequest) {
            return $pre;
        }
        if (! $pre->isEditable()) {
            return response()->json(['message' => __('Ce dossier n’est plus modifiable.')], 422);
        }

        $data = $request->validate([
            'data' => ['required', 'array', 'max:30'],
            'data.*' => ['nullable', 'string', 'max:300'],
        ]);
        $pre->update(['data' => $data['data']]);

        return response()->json(['data' => $this->view($pre->fresh()->load(['service', 'documents']))]);
    }

    public function upload(Request $request, int $id, DocumentStorage $storage): JsonResponse
    {
        $pre = $this->owned($request, $id);
        if (! $pre instanceof PreRequest) {
            return $pre;
        }
        if (! $pre->isEditable()) {
            return response()->json(['message' => __('Ce dossier n’est plus modifiable.')], 422);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'min:2', 'max:160'],
            'file' => ['required', 'file', 'max:'.RequestDocument::MAX_KB],
            // Date d'expiration de la pièce (passeport, carte d'identité…), facultative.
            'expires_on' => ['nullable', 'date', 'after:today'],
        ]);

        try {
            $doc = $storage->store($pre, $request->file('file'), (string) $request->input('label'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        if (! empty($data['expires_on'])) {
            $doc->update(['expires_on' => $data['expires_on']]);
        }

        return response()->json(['data' => $this->docView($doc)], 201);
    }

    public function deleteDocument(Request $request, int $id, int $docId): JsonResponse
    {
        $pre = $this->owned($request, $id);
        if (! $pre instanceof PreRequest) {
            return $pre;
        }
        if (! $pre->isEditable()) {
            return response()->json(['message' => __('Ce dossier n’est plus modifiable.')], 422);
        }

        $doc = RequestDocument::where('pre_request_id', $pre->id)->findOrFail($docId);
        Storage::disk(RequestDocument::DISK)->delete($doc->stored_path);
        $doc->delete();

        return response()->json(['message' => __('Pièce supprimée.')]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $pre = $this->owned($request, $id);
        if (! $pre instanceof PreRequest) {
            return $pre;
        }
        if (! $pre->isEditable()) {
            return response()->json(['message' => __('Ce dossier a déjà été soumis.')], 422);
        }
        if ($pre->documents()->count() === 0) {
            return response()->json(['message' => __('Ajoutez au moins une pièce avant de soumettre.')], 422);
        }
        // Après un renvoi : une pièce refusée par le consulat doit être retirée et remplacée avant le nouvel envoi.
        $refused = $pre->documents()->where('status', 'rejected')->pluck('label');
        if ($refused->isNotEmpty()) {
            return response()->json([
                'message' => __('Remplacez d’abord les pièces refusées :').' '.$refused->implode(', '),
            ], 422);
        }

        // Dossier complet obligatoire : les pièces exigées par le service doivent toutes être déposées.
        $missing = $pre->documentCheck()['missing'];
        if ($missing !== []) {
            return response()->json([
                'message' => __('Dossier incomplet. Pièces encore manquantes :').' '.implode(' · ', $missing),
                'missing_documents' => $missing,
            ], 422);
        }

        // Validité du passeport : si le service (ex. visa) l'exige, le passeport doit rester valable au moins N mois.
        $minMonths = $pre->service?->min_passport_validity_months;
        if ($minMonths) {
            $limit = now()->addMonths((int) $minMonths)->toDateString();
            $passport = $pre->documents->first(fn ($d) => preg_match('/passeport|passport|جواز/iu', (string) $d->label) && $d->expires_on);
            if (! $passport || $passport->expires_on->lt($limit)) {
                return response()->json([
                    'message' => __('Passeport valable au moins :months mois requis. Indiquez sa date d’expiration dans la pièce « Passeport ».', ['months' => $minMonths]),
                ], 422);
            }
        }

        $wasReturned = $pre->status === 'returned';
        $pre->update(['status' => 'submitted', 'submitted_at' => now()]);
        $pre->recordEvent('submitted', $wasReturned ? __('Nouvel envoi après complément') : null, null, 'citizen');

        return response()->json([
            'message' => __('Votre pré-demande a été transmise au consulat.'),
            'data' => $this->view($pre->fresh()->load(['service', 'documents'])),
        ]);
    }

    /** Citoyen authentifié, rattaché au consulat de la route (et actif si demandé). */
    private function citizen(Request $request, bool $active = false): Citizen|JsonResponse
    {
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Connectez-vous pour accéder à votre espace.')], 401);
        }
        if ($citizen->consulate_id !== $this->context->require()->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }
        if ($active && ! $citizen->isActive()) {
            return response()->json(['message' => __('Votre compte n’est pas encore actif.')], 403);
        }

        return $citizen;
    }

    private function owned(Request $request, int $id): PreRequest|JsonResponse
    {
        $citizen = $this->citizen($request);
        if (! $citizen instanceof Citizen) {
            return $citizen;
        }

        return PreRequest::withoutGlobalScopes()->where('citizen_id', $citizen->id)->where('id', $id)->first()
            ?? response()->json(['message' => __('Dossier introuvable.')], 404);
    }

    private function view(PreRequest $p): array
    {
        $p->loadMissing(['service', 'documents', 'events']);
        $check = $p->documentCheck();

        return [
            'id' => $p->id,
            'service' => $p->service?->slug,
            'service_name' => $p->service?->name,
            // Frais en vigueur à la date du jour (affichés avant le dépôt, à régler au guichet).
            'fee' => $p->service?->feeOn()?->label(),
            'is_urgent' => (bool) $p->is_urgent,
            // Cas particulier signalé par le consulat, et validité du document délivré (carte, visa, passeport).
            'special_case' => $p->special_case,
            'special_case_label' => $p->special_case ? __(PreRequest::SPECIAL_CASES[$p->special_case] ?? $p->special_case) : null,
            'valid_until' => $p->valid_until?->toDateString(),
            'status' => $p->status,
            'status_label' => __(PreRequest::STATUSES[$p->status] ?? $p->status),
            'data' => $p->data ?? [],
            'staff_note' => $p->staff_note,
            'editable' => $p->isEditable(),
            'submitted_at' => $p->submitted_at?->toIso8601String(),
            'documents' => $p->documents->map(fn (RequestDocument $d) => $this->docView($d))->values(),
            // Pièces du service : liste proposée au dépôt, et pièces encore manquantes.
            'required_documents' => array_map(fn ($i) => $i['label'], $p->service?->documentItems() ?? []),
            'missing_documents' => $check['missing'],
            'conditional_documents' => $check['conditional'],
            // Historique visible du citoyen : étapes et décisions, sans le nom des agents.
            'history' => $p->events->map(fn ($e) => [
                'label' => $e->label(),
                'note' => $e->type === 'assigned' ? null : $e->note,
                'by' => $e->actor === 'citizen' ? 'citizen' : 'consulate',
                'at' => $e->created_at?->toIso8601String(),
            ])->filter(fn ($e) => $e['label'] !== __(PreRequest::EVENTS['assigned']))->values(),
        ];
    }

    private function docView(RequestDocument $d): array
    {
        return [
            'id' => $d->id,
            'label' => $d->label,
            'original_name' => $d->original_name,
            'size' => $d->size,
            'status' => $d->status,
            'status_label' => __(RequestDocument::STATUSES[$d->status] ?? $d->status),
            'review_note' => $d->review_note,
            'expires_on' => $d->expires_on?->toDateString(),
            'expiry_state' => $d->expiryState(),
        ];
    }
}
