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
            'full_name' => $a->full_name,
            'cancellable' => $a->isCancellable(),
            'cancelled_at' => $a->cancelled_at?->toIso8601String(),
        ];
    }
}
