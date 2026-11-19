<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Mail\ClosureNotice;
use App\Models\Appointment;
use App\Models\ClosedDate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Prévient par email les citoyens dont le rendez-vous tombe un jour fermé.
 * Un seul email par adresse ; l'échec d'envoi n'empêche jamais l'enregistrement de la fermeture.
 */
class NotifyClosure
{
    /** @return int nombre de citoyens prévenus */
    public function execute(ClosedDate $closure): int
    {
        $appointments = Appointment::withoutGlobalScopes()
            ->where('consulate_id', $closure->consulate_id)
            ->whereDate('appointment_date', $closure->date->toDateString())
            ->whereIn('status', AppointmentStatus::occupying())
            ->with(['consulate', 'service'])
            ->get();

        $sent = 0;
        foreach ($appointments->unique('email') as $appointment) {
            try {
                Mail::to($appointment->email)->locale($appointment->locale ?: 'fr')
                    ->send(new ClosureNotice($appointment, $closure));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Alerte de fermeture non envoyée', ['appointment' => $appointment->id, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }
}
