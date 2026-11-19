<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Arrivée au guichet : enregistre l'heure et attribue le prochain numéro de ticket du jour pour ce consulat.
 * Le numéro est calculé sous verrou pour éviter deux tickets identiques au même instant.
 */
class CheckInAppointment
{
    public function execute(Appointment $appointment): Appointment
    {
        if ($appointment->checked_in_at !== null) {
            return $appointment;
        }

        if (! in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)
            || ! $appointment->appointment_date->isToday()) {
            throw new RuntimeException('Arrivée possible seulement le jour d’un rendez-vous en attente ou confirmé.');
        }

        return DB::transaction(function () use ($appointment) {
            $last = Appointment::withoutGlobalScopes()
                ->where('consulate_id', $appointment->consulate_id)
                ->whereDate('appointment_date', $appointment->appointment_date->toDateString())
                ->lockForUpdate()
                ->max('queue_number');

            $appointment->forceFill([
                'checked_in_at' => now(),
                'queue_number' => ((int) $last) + 1,
            ])->save();

            return $appointment;
        });
    }

    /** Numéro affiché : A-007. */
    public static function label(?int $number): ?string
    {
        return $number ? 'A-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT) : null;
    }
}
