<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Exceptions\BookingException;
use App\Models\Appointment;

/** Annulation d'un rendez-vous par le visiteur (jeton requis, délai minimal respecté). */
class CancelAppointment
{
    /** @throws BookingException */
    public function execute(Appointment $appointment): Appointment
    {
        if (! $appointment->isCancellable()) {
            throw BookingException::notCancellable();
        }

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $appointment;
    }
}
