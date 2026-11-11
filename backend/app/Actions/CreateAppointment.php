<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Exceptions\BookingException;
use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\AvailabilityService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Crée un rendez-vous en garantissant l'absence de double réservation.
 *
 * Dans une transaction : verrou (SELECT … FOR UPDATE) sur le créneau,
 * recomptage des places, refus si complet ou si le même email réserve déjà ce créneau.
 */
class CreateAppointment
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /**
     * @param  array{full_name:string,email:string,phone:string,notes?:?string}  $data
     *
     * @throws BookingException
     */
    public function execute(Consulate $consulate, Service $service, TimeSlot $slot, CarbonInterface $date, array $data, string $locale = 'fr'): Appointment
    {
        // Cohérence : le service et le créneau appartiennent bien à ce consulat.
        $valid = $service->consulate_id === $consulate->id
            && $slot->consulate_id === $consulate->id
            && $service->is_active && $service->allow_appointment
            && $slot->is_active
            && ($slot->service_id === null || $slot->service_id === $service->id)
            && $slot->weekday === $date->dayOfWeek
            && $this->availability->isWithinWindow($date);

        if (! $valid) {
            throw BookingException::invalid();
        }

        $appointment = DB::transaction(function () use ($consulate, $service, $slot, $date, $data, $locale) {
            $locked = TimeSlot::withoutGlobalScopes()->whereKey($slot->id)->lockForUpdate()->firstOrFail();

            $occupied = Appointment::withoutGlobalScopes()
                ->where('time_slot_id', $locked->id)
                ->whereDate('appointment_date', $date->toDateString())
                ->whereIn('status', AppointmentStatus::occupying());

            if ((clone $occupied)->count() >= $locked->capacity) {
                throw BookingException::slotFull();
            }

            if ((clone $occupied)->whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->exists()) {
                throw BookingException::duplicate();
            }

            return Appointment::withoutGlobalScopes()->create([
                'consulate_id' => $consulate->id,
                'service_id' => $service->id,
                'time_slot_id' => $locked->id,
                'reference' => Appointment::generateReference($consulate->ref_prefix, $date->toDateString()),
                'appointment_date' => $date->toDateString(),
                'start_time' => $locked->start_time,
                'end_time' => $locked->end_time,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'notes' => $data['notes'] ?? null,
                'status' => AppointmentStatus::Pending,
                'locale' => $locale,
            ]);
        });

        $this->sendConfirmation($appointment);

        return $appointment;
    }

    /** L'échec d'envoi d'email ne doit jamais annuler un rendez-vous déjà enregistré. */
    private function sendConfirmation(Appointment $appointment): void
    {
        try {
            Mail::to($appointment->email)
                ->locale($appointment->locale)
                ->send(new AppointmentConfirmation($appointment));
        } catch (\Throwable $e) {
            Log::warning('Email de confirmation non envoyé', [
                'reference' => $appointment->reference,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
