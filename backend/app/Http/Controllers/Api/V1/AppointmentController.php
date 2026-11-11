<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelAppointment;
use App\Actions\CreateAppointment;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\AvailabilityService;
use App\Services\ConsulateContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Disponibilités, prise de rendez-vous, consultation / annulation par jeton, export .ics. */
class AppointmentController extends Controller
{
    /** Jours réservables pour un service : GET /v1/{consulate}/availability/dates?service=slug */
    public function dates(Request $request, ConsulateContext $context, AvailabilityService $availability): JsonResponse
    {
        $consulate = $context->require();
        $service = $this->service($request, $consulate);

        return response()->json([
            'data' => $availability->availableDates($consulate, $service),
            'window' => [
                'from' => $availability->firstBookableDate()->toDateString(),
                'to' => $availability->lastBookableDate()->toDateString(),
            ],
        ]);
    }

    /** Créneaux d'un jour : GET /v1/{consulate}/availability/slots?service=slug&date=Y-m-d */
    public function slots(Request $request, ConsulateContext $context, AvailabilityService $availability): JsonResponse
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $consulate = $context->require();
        $service = $this->service($request, $consulate);

        $slots = $availability->slotsFor($consulate, $service, Carbon::createFromFormat('Y-m-d', $request->query('date'))->startOfDay());

        return response()->json(['data' => $slots->map(fn (TimeSlot $s) => [
            'id' => $s->id,
            'start_time' => substr((string) $s->start_time, 0, 5),
            'end_time' => substr((string) $s->end_time, 0, 5),
            'remaining' => $s->remaining,
        ])->values()]);
    }

    /** Création : POST /v1/{consulate}/appointments */
    public function store(BookingRequest $request, ConsulateContext $context, CreateAppointment $create): JsonResponse
    {
        // Honeypot : un robot remplit ce champ. On répond comme si tout allait bien.
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Demande enregistrée.'], 201);
        }

        $key = 'booking:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Trop de tentatives. Réessayez dans une minute.'], 429);
        }
        RateLimiter::hit($key, 60);

        $consulate = $context->require();
        $service = Service::withoutGlobalScopes()
            ->where('consulate_id', $consulate->id)->where('slug', $request->input('service'))
            ->where('is_active', true)->where('allow_appointment', true)->first();
        $slot = TimeSlot::withoutGlobalScopes()->where('consulate_id', $consulate->id)->find($request->input('slot_id'));

        if (! $service || ! $slot) {
            return response()->json(['message' => __('Ce créneau n’est pas disponible pour le service ou la date choisis.')], 422);
        }

        try {
            $appointment = $create->execute(
                $consulate,
                $service,
                $slot,
                Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay(),
                [
                    'full_name' => trim($request->input('full_name')),
                    'email' => trim($request->input('email')),
                    'phone' => trim($request->input('phone')),
                    'notes' => trim((string) $request->input('notes')) ?: null,
                    'consent' => $request->boolean('consent'),
                ],
                app()->getLocale(),
            );
        } catch (BookingException $e) {
            return response()->json(['message' => __($e->translationKey)], 422);
        }

        // Le jeton n'est renvoyé qu'une seule fois, ici. Il permet de consulter et d'annuler.
        return response()->json([
            'reference' => $appointment->reference,
            'access_token' => $appointment->plainAccessToken,
            'data' => new AppointmentResource($appointment),
        ], 201);
    }

    /** Consultation : GET /v1/{consulate}/appointments/{reference}?token=… */
    public function show(Request $request, ConsulateContext $context, string $reference): AppointmentResource|JsonResponse
    {
        $appointment = $this->findWithToken($request, $context, $reference);

        return $appointment instanceof Appointment
            ? new AppointmentResource($appointment->load('service'))
            : $appointment;
    }

    /** Annulation : POST /v1/{consulate}/appointments/{reference}/cancel?token=… */
    public function cancel(Request $request, ConsulateContext $context, CancelAppointment $cancel, string $reference): AppointmentResource|JsonResponse
    {
        $appointment = $this->findWithToken($request, $context, $reference);
        if (! $appointment instanceof Appointment) {
            return $appointment;
        }

        try {
            $cancel->execute($appointment->load('service'));
        } catch (BookingException $e) {
            return response()->json(['message' => __($e->translationKey)], 422);
        }

        return new AppointmentResource($appointment->refresh()->load('service'));
    }

    /** Export .ics : GET /v1/{consulate}/appointments/{reference}/ics?token=… */
    public function ics(Request $request, ConsulateContext $context, string $reference)
    {
        $appointment = $this->findWithToken($request, $context, $reference);
        if (! $appointment instanceof Appointment) {
            return $appointment;
        }

        $consulate = $context->require();
        $start = Carbon::parse($appointment->appointment_date->toDateString().' '.$appointment->start_time)->format('Ymd\THis');
        $end = Carbon::parse($appointment->appointment_date->toDateString().' '.$appointment->end_time)->format('Ymd\THis');
        $summary = 'Rendez-vous '.$appointment->reference.' — '.$consulate->name;
        $location = str_replace(["\n", ',', ';'], [' ', '\\,', '\\;'], (string) $consulate->address);

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Consulat Demo//FR', 'BEGIN:VEVENT',
            'UID:'.$appointment->reference.'@consulat-demo.test',
            'DTSTAMP:'.now()->format('Ymd\THis\Z'),
            'DTSTART:'.$start, 'DTEND:'.$end,
            'SUMMARY:'.$summary, 'LOCATION:'.$location,
            'DESCRIPTION:Démo / maquette non officielle. Pensez à apporter vos documents.',
            'END:VEVENT', 'END:VCALENDAR',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$appointment->reference.'.ics"',
        ]);
    }

    private function service(Request $request, $consulate): Service
    {
        $request->validate(['service' => ['required', 'string']]);

        return $consulate->services()->active()->where('allow_appointment', true)
            ->where('slug', $request->query('service'))->firstOrFail();
    }

    /** Renvoie le rendez-vous si le jeton est valide, sinon une réponse 404 générique. */
    private function findWithToken(Request $request, ConsulateContext $context, string $reference): Appointment|JsonResponse
    {
        $appointment = $context->require()->appointments()->where('reference', $reference)->first();

        $token = $request->bearerToken() ?? (string) $request->query('token');

        if (! $appointment || ! $appointment->checkAccessToken($token)) {
            return response()->json(['message' => 'Rendez-vous introuvable.'], 404);
        }

        return $appointment;
    }
}
