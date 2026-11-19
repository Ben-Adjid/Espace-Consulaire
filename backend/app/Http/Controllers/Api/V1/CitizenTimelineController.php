<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\PreRequest;
use App\Models\PreRequestEvent;
use App\Models\RequestDocument;
use App\Services\ConsulateContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chronologie unique de la démarche du citoyen : dossiers en ligne, rendez-vous, passage au guichet,
 * suivi du document, et échéances (pièces et validités). Une seule suite, du plus récent au plus ancien.
 * Les événements internes (attribution à un agent) ne sont jamais montrés au citoyen.
 */
class CitizenTimelineController extends Controller
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

        $items = collect();

        $pres = PreRequest::withoutGlobalScopes()->where('citizen_id', $citizen->id)->with(['service', 'events', 'documents'])->get();
        foreach ($pres as $pre) {
            $service = $pre->service?->name;
            foreach ($pre->events as $event) {
                if ($event->type === 'assigned') {
                    continue;
                }
                $items->push($this->item($event->created_at, $event->label(), $event->note, $service, 'demarche'));
            }
            if ($pre->valid_until) {
                $items->push($this->item($pre->valid_until, __('Validité du document : fin de validité'), null, $service, 'echeance'));
            }
            foreach ($pre->documents as $doc) {
                if ($doc->expires_on) {
                    $state = $doc->expiryState();
                    $items->push($this->item($doc->expires_on, __('Pièce :label : expiration', ['label' => $doc->label]), $state === 'expired' ? __('Pièce expirée : à remplacer.') : null, $service, 'echeance'));
                }
            }
        }

        $appointments = Appointment::withoutGlobalScopes()->where('citizen_id', $citizen->id)->with('service')->get();
        foreach ($appointments as $a) {
            $service = $a->service?->name;
            $items->push($this->item($a->created_at, __('Rendez-vous pris'), $a->appointment_date->toDateString(), $service, 'rendez-vous'));
            if ($a->checked_in_at) {
                $items->push($this->item($a->checked_in_at, __('Arrivé au guichet'), $a->queue_number ? 'A-'.str_pad((string) $a->queue_number, 3, '0', STR_PAD_LEFT) : null, $service, 'guichet'));
            }
            if ($a->case_status && $a->case_updated_at) {
                $items->push($this->item($a->case_updated_at, __('Suivi du document : :status', ['status' => $a->case_status->label()]), $a->case_note, $service, 'suivi'));
            }
        }

        return response()->json(['data' => $items->sortByDesc('at')->values()]);
    }

    /** @return array{at: ?string, title: string, detail: ?string, service: ?string, kind: string} */
    private function item($at, string $title, ?string $detail, ?string $service, string $kind): array
    {
        return [
            'at' => $at?->toIso8601String() ?? (is_string($at) ? $at : null),
            'title' => $title,
            'detail' => $detail,
            'service' => $service,
            'kind' => $kind,
        ];
    }
}
