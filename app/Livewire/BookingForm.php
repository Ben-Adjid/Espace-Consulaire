<?php

namespace App\Livewire;

use App\Actions\CreateAppointment;
use App\Exceptions\BookingException;
use App\Http\Middleware\SetCurrentConsulate;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Prise de rendez-vous en 4 étapes : service → date → créneau → coordonnées.
 * Toute la logique de capacité est dans AvailabilityService / CreateAppointment.
 */
class BookingForm extends Component
{
    #[Locked]
    public int $consulateId;

    #[Locked]
    public string $locale = 'fr';

    public ?int $serviceId = null;

    public ?string $date = null;

    public ?int $slotId = null;

    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    /** Honeypot : invisible pour un humain, rempli par les robots. */
    public string $website = '';

    public function mount(int $consulateId, string $locale, string $preselected = ''): void
    {
        $this->consulateId = $consulateId;
        $this->locale = $locale;
        SetCurrentConsulate::applyLocale($locale);

        if ($preselected !== '') {
            $this->serviceId = $this->consulate()->services()->active()
                ->where('allow_appointment', true)->where('slug', $preselected)->value('id');
        }
    }

    /** Les requêtes Livewire repassent par ici : on restaure la langue du visiteur. */
    public function hydrate(): void
    {
        SetCurrentConsulate::applyLocale($this->locale);
    }

    private function consulate(): Consulate
    {
        return Consulate::query()->where('is_active', true)->findOrFail($this->consulateId);
    }

    private function selectedService(): ?Service
    {
        return $this->serviceId
            ? $this->consulate()->services()->active()->where('allow_appointment', true)->find($this->serviceId)
            : null;
    }

    public function updatedServiceId(): void
    {
        $this->reset(['date', 'slotId']);
        $this->resetErrorBag();
    }

    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->slotId = null;
        $this->resetErrorBag();
    }

    public function submit(AvailabilityService $availability, CreateAppointment $create)
    {
        // Robot : on feint le succès sans rien enregistrer.
        if ($this->website !== '') {
            return redirect()->to('/'.$this->consulate()->slug);
        }

        $key = 'booking:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('full_name', __('Trop de tentatives. Réessayez dans une minute.'));

            return null;
        }
        RateLimiter::hit($key, 60);

        $this->validate([
            'serviceId' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'slotId' => ['required', 'integer'],
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^[0-9+().\s-]{6,32}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'serviceId' => __('Service'),
            'date' => __('Date'),
            'slotId' => __('Créneau'),
            'full_name' => __('Nom complet'),
            'email' => __('Email'),
            'phone' => __('Téléphone'),
            'notes' => __('Message'),
        ]);

        $consulate = $this->consulate();
        $service = $this->selectedService();
        $slot = TimeSlot::withoutGlobalScopes()->where('consulate_id', $consulate->id)->find($this->slotId);

        if (! $service || ! $slot) {
            $this->addError('slotId', __(BookingException::invalid()->translationKey));

            return null;
        }

        try {
            $appointment = $create->execute(
                $consulate,
                $service,
                $slot,
                Carbon::createFromFormat('Y-m-d', $this->date)->startOfDay(),
                [
                    'full_name' => trim($this->full_name),
                    'email' => trim($this->email),
                    'phone' => trim($this->phone),
                    'notes' => trim($this->notes) ?: null,
                ],
                $this->locale,
            );
        } catch (BookingException $e) {
            $this->addError('slotId', __($e->translationKey));
            $this->slotId = null;

            return null;
        }

        return redirect()->route('appointment.confirmation', [
            'consulate' => $consulate->slug,
            'reference' => $appointment->reference,
        ]);
    }

    public function render(AvailabilityService $availability)
    {
        $consulate = $this->consulate();
        $service = $this->selectedService();

        $dates = $service ? $availability->availableDates($consulate, $service) : [];

        $slots = collect();
        if ($service && $this->date && in_array($this->date, $dates, true)) {
            $slots = $availability->slotsFor($consulate, $service, Carbon::createFromFormat('Y-m-d', $this->date)->startOfDay());
        }

        return view('livewire.booking-form', [
            'consulate' => $consulate,
            'services' => $consulate->services()->active()->ordered()->where('allow_appointment', true)->get(),
            'service' => $service,
            'dates' => array_slice($dates, 0, 24),
            'slots' => $slots,
        ]);
    }
}
