<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Exceptions\BookingException;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Services\AvailabilityService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Déplacement d'un rendez-vous vers un autre créneau par le visiteur (jeton requis).
 * Mêmes règles que la création : créneau valide, capacité, jour non fermé, fenêtre de réservation.
 * Le délai minimal d'annulation s'applique aussi au déplacement.
 */
class RescheduleAppointment
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /** @throws BookingException */
    public function execute(Appointment $appointment, TimeSlot $slot, CarbonInterface $date): Appointment
    {
        if (! $appointment->isCancellable()) {
            throw BookingException::notReschedulable();
        }

        $consulate = $appointment->consulate;
        $service = $appointment->service;

        $valid = $slot->consulate_id === $consulate->id
            && $slot->is_active
            && ($slot->service_id === null || $slot->service_id === $service->id)
            && $slot->weekday === $date->dayOfWeek
            && $this->availability->isWithinWindow($date)
            && ! $this->availability->isClosed($consulate, $date);

        if (! $valid) {
            throw BookingException::invalid();
        }

        return DB::transaction(function () use ($appointment, $slot, $date) {
            $locked = TimeSlot::withoutGlobalScopes()->whereKey($slot->id)->lockForUpdate()->firstOrFail();

            $occupied = Appointment::withoutGlobalScopes()
                ->where('time_slot_id', $locked->id)
                ->whereDate('appointment_date', $date->toDateString())
                ->whereIn('status', AppointmentStatus::occupying())
                ->where('id', '!=', $appointment->id);

            if ((clone $occupied)->count() >= $locked->capacity) {
                throw BookingException::slotFull();
            }

            $appointment->update([
                'time_slot_id' => $locked->id,
                'appointment_date' => $date->toDateString(),
                'start_time' => $locked->start_time,
                'end_time' => $locked->end_time,
            ]);

            return $appointment->refresh();
        });
    }
}
