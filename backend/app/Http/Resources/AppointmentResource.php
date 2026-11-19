<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue d'un rendez-vous côté visiteur. Ne renvoie que le strict nécessaire :
 * jamais l'email complet ni les notes (confidentialité).
 *
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $a = $this->resource;

        return [
            'reference' => $a->reference,
            'status' => $a->status->value,
            'status_label' => $a->status->getLabel(),
            'date' => $a->appointment_date->toDateString(),
            'start_time' => substr((string) $a->start_time, 0, 5),
            'end_time' => substr((string) $a->end_time, 0, 5),
            'service' => $a->service?->name,
            'service_slug' => $a->service?->slug,
            'full_name' => $a->full_name,
            'cancellable' => $a->isCancellable(),
            'cancelled_at' => $a->cancelled_at?->toIso8601String(),
            // Ticket de file d'attente attribué à l'arrivée au guichet.
            'checked_in_at' => $a->checked_in_at?->toIso8601String(),
            'ticket' => \App\Actions\CheckInAppointment::label($a->queue_number),
            // Suivi du dossier après le dépôt.
            'case_status' => $a->case_status?->value,
            'case_status_label' => $a->case_status?->label(),
            'case_note' => $a->case_note,
            'case_updated_at' => $a->case_updated_at?->toIso8601String(),
            'on_behalf_name' => $a->on_behalf_name,
            // Dossier en ligne lié à cette démarche : seulement son statut et son identifiant, jamais ses pièces.
            'pre_request' => $a->preRequest ? [
                'id' => $a->preRequest->id,
                'status' => $a->preRequest->status,
                'status_label' => __(\App\Models\PreRequest::STATUSES[$a->preRequest->status] ?? $a->preRequest->status),
            ] : null,
        ];
    }
}
