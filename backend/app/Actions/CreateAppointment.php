<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Exceptions\BookingException;
use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Consulate;
use App\Models\PreRequest;
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
 * recomptage des places, refus si complet, si le même email réserve déjà ce créneau,
 * ou si le jour est fermé. Le consentement RGPD est obligatoire.
 */
class CreateAppointment
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /**
     * @param  array{full_name:string,email:string,phone:string,notes?:?string,consent:bool}  $data
     *
     * @throws BookingException
     */
    public function execute(Consulate $consulate, Service $service, TimeSlot $slot, CarbonInterface $date, array $data, string $locale = 'fr'): Appointment
    {
        if (empty($data['consent'])) {
            throw BookingException::consentRequired();
        }

        $valid = $service->consulate_id === $consulate->id
            && $slot->consulate_id === $consulate->id
            && $service->is_active && $service->allow_appointment
            && $slot->is_active
            && ($slot->service_id === null || $slot->service_id === $service->id)
            && $slot->weekday === $date->dayOfWeek
            && $this->availability->isWithinWindow($date)
            && ! $this->availability->isClosed($consulate, $date);

        if (! $valid) {
            throw BookingException::invalid();
        }

        $preRequest = $this->resolvePreRequest($consulate, $service, $data);

        $appointment = DB::transaction(function () use ($consulate, $service, $slot, $date, $data, $locale, $preRequest) {
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
                'citizen_id' => $data['citizen_id'] ?? null,
                'pre_request_id' => $preRequest?->id,
                'on_behalf_name' => $data['on_behalf_name'] ?? null,
                'on_behalf_relation' => $data['on_behalf_relation'] ?? null,
                'residence_country' => $data['residence_country'] ?? null,
                'nationality_declared' => (bool) ($data['nationality_declared'] ?? false),
                'notes' => $data['notes'] ?? null,
                'status' => AppointmentStatus::Pending,
                'locale' => $locale,
                'consent_at' => now(),
            ]);
        });

        $appointment->issueAccessToken();
        $this->sendConfirmation($appointment);

        return $appointment;
    }

    /**
     * Dossier en ligne de la démarche, rattaché au rendez-vous.
     * - Rattachement explicite (pre_request_id) : le dossier doit appartenir au citoyen, au même consulat et au même service,
     *   et être VALIDÉ. Sinon refus.
     * - Sans rattachement explicite : le dernier dossier validé pour ce service est rattaché automatiquement.
     *   S'il n'y en a pas, le rendez-vous reste possible : le guichet verra que le dossier n'est pas validé.
     * Un visiteur sans compte n'a pas de dossier.
     */
    private function resolvePreRequest(Consulate $consulate, Service $service, array $data): ?PreRequest
    {
        $citizenId = $data['citizen_id'] ?? null;
        if (! $citizenId) {
            return null;
        }

        $base = PreRequest::withoutGlobalScopes()
            ->where('citizen_id', $citizenId)
            ->where('consulate_id', $consulate->id)
            ->where('service_id', $service->id);

        if (! empty($data['pre_request_id'])) {
            $explicit = (clone $base)->whereKey((int) $data['pre_request_id'])->first();
            if (! $explicit) {
                throw BookingException::preRequestNotFound();
            }
            if ($explicit->status !== 'validated') {
                throw BookingException::preRequestNotValidated();
            }

            return $explicit;
        }

        $validated = (clone $base)->where('status', 'validated')->latest('reviewed_at')->first();

        // Règle du service : certains services ne se traitent qu'à partir d'un dossier validé.
        if ($validated === null && $service->requires_validated_file) {
            throw BookingException::preRequestRequired();
        }

        return $validated;
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
