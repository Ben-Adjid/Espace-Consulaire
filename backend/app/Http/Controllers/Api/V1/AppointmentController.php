<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelAppointment;
use App\Actions\CreateAppointment;
use App\Actions\RescheduleAppointment;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\AvailabilityService;
use App\Services\ConsulateContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $consulate = $context->require();

        // Compte citoyen obligatoire : la réservation est rattachée au compte, actif et du même consulat.
        $citizen = $request->user('sanctum');
        if (! $citizen instanceof Citizen) {
            return response()->json(['message' => __('Créez votre compte et connectez-vous pour prendre rendez-vous.')], 401);
        }
        if ($citizen->consulate_id !== $consulate->id) {
            return response()->json(['message' => __('Ce compte n’est pas rattaché à ce consulat.')], 403);
        }
        if (! $citizen->isActive()) {
            return response()->json(['message' => __('Votre compte doit être validé par le consulat avant de prendre rendez-vous.')], 403);
        }

        // Rattachement par pays de résidence : c'est le pays du compte, validé par le consulat.
        $declared = trim((string) $request->input('residence_country'));
        if ($declared !== '' && Consulate::countryKey($declared) !== Consulate::countryKey($citizen->residence_country)) {
            return response()->json([
                'message' => __('Ce pays ne correspond pas à votre compte. Votre pays de résidence validé est :country.', ['country' => $citizen->residence_country]),
                'errors' => ['residence_country' => [__('Pays de résidence différent de celui du compte.')]],
            ], 422);
        }
        $residence = $citizen->residence_country;
        if (! $consulate->covers($residence)) {
            $suggested = Consulate::query()
                ->where('is_active', true)
                ->where('id', '!=', $consulate->id)
                ->get()
                ->first(fn (Consulate $c) => $c->representedKey() === $consulate->representedKey() && $c->covers($residence));

            return response()->json([
                'message' => $suggested
                    ? __('Votre pays de résidence est rattaché au consulat :name. Veuillez réserver là-bas.', ['name' => $suggested->name])
                    : __('Aucun consulat en ligne ne couvre encore votre pays de résidence.'),
                'errors' => ['residence_country' => [__('Pays de résidence non couvert par ce consulat.')]],
                'suggested_consulate' => $suggested ? ['slug' => $suggested->slug, 'name' => $suggested->name] : null,
            ], 422);
        }

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
                    'full_name' => $citizen->full_name,
                    'email' => $citizen->email,
                    'phone' => $citizen->phone,
                    'notes' => trim((string) $request->input('notes')) ?: null,
                    'consent' => $request->boolean('consent'),
                    'residence_country' => $residence,
                    'nationality_declared' => true,
                    'citizen_id' => $citizen->id,
                    'pre_request_id' => $request->integer('pre_request_id') ?: null,
                    'on_behalf_name' => trim((string) $request->input('on_behalf_name')) ?: null,
                    'on_behalf_relation' => $request->input('on_behalf_name') ? $request->input('on_behalf_relation') : null,
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
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Consulat//FR', 'BEGIN:VEVENT',
            'UID:'.$appointment->reference.'@consulat',
            'DTSTAMP:'.now()->format('Ymd\THis\Z'),
            'DTSTART:'.$start, 'DTEND:'.$end,
            'SUMMARY:'.$summary, 'LOCATION:'.$location,
            'DESCRIPTION:Pensez à apporter vos documents.',
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
    /** Déplacement : POST /v1/{consulate}/appointments/{reference}/reschedule?token=… (body: date, slot_id) */
    public function reschedule(Request $request, ConsulateContext $context, RescheduleAppointment $reschedule, string $reference): AppointmentResource|JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'slot_id' => ['required', 'integer'],
        ]);

        $appointment = $this->findWithToken($request, $context, $reference);
        if (! $appointment instanceof Appointment) {
            return $appointment;
        }

        $slot = TimeSlot::withoutGlobalScopes()
            ->where('consulate_id', $appointment->consulate_id)
            ->find($request->input('slot_id'));

        if (! $slot) {
            return response()->json(['message' => __('Ce créneau n’est pas disponible pour le service ou la date choisis.')], 422);
        }

        try {
            $updated = $reschedule->execute(
                $appointment->load('service'),
                $slot,
                Carbon::createFromFormat('Y-m-d', $request->input('date'))->startOfDay(),
            );
        } catch (BookingException $e) {
            return response()->json(['message' => __($e->translationKey)], 422);
        }

        return new AppointmentResource($updated->load('service'));
    }

    private function findWithToken(Request $request, ConsulateContext $context, string $reference): Appointment|JsonResponse
    {
        $appointment = $context->require()->appointments()->where('reference', $reference)->first();

        // Le titulaire du compte peut gérer ses rendez-vous sans lien personnel.
        $owner = $request->user('sanctum');
        if ($appointment && $owner instanceof Citizen && $appointment->citizen_id === $owner->id) {
            return $appointment;
        }

        $token = $request->bearerToken() ?? (string) $request->query('token');

        if (! $appointment || ! $appointment->checkAccessToken($token)) {
            return response()->json(['message' => 'Rendez-vous introuvable.'], 404);
        }

        return $appointment;
    }
}
