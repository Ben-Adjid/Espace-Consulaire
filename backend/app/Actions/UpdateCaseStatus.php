<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Mail\CaseStatusMail;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Passe le dossier à une nouvelle étape et prévient le citoyen par email (l'échec d'envoi ne bloque rien). */
class UpdateCaseStatus
{
    public function execute(Appointment $appointment, CaseStatus $status, ?string $note = null): Appointment
    {
        $appointment->forceFill([
            'case_status' => $status,
            'case_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            'case_updated_at' => now(),
        ])->save();

        try {
            Mail::to($appointment->email)->locale($appointment->locale ?: 'fr')->send(new CaseStatusMail($appointment->load('service')));
        } catch (\Throwable $e) {
            Log::warning('Avis de dossier non envoyé', ['appointment' => $appointment->id, 'error' => $e->getMessage()]);
        }

        return $appointment;
    }
}
