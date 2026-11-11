<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Calcule les jours et créneaux réservables d'un service :
 * créneau hebdomadaire actif − rendez-vous déjà pris (capacité), hors jours fermés.
 */
class AvailabilityService
{
    public function firstBookableDate(): CarbonInterface
    {
        return today()->addDays(config('booking.min_lead_days'));
    }

    public function lastBookableDate(): CarbonInterface
    {
        return today()->addDays(config('booking.horizon_days'));
    }

    public function isWithinWindow(CarbonInterface $date): bool
    {
        return $date->betweenIncluded($this->firstBookableDate(), $this->lastBookableDate());
    }

    public function isClosed(Consulate $consulate, CarbonInterface $date): bool
    {
        return ClosedDate::withoutGlobalScopes()
            ->where('consulate_id', $consulate->id)
            ->whereDate('date', $date->toDateString())
            ->exists();
    }

    /** Créneaux actifs applicables à ce service (spécifiques ou « tous services »). */
    private function slotsQuery(Consulate $consulate, Service $service)
    {
        return TimeSlot::withoutGlobalScopes()
            ->where('consulate_id', $consulate->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('service_id')->orWhere('service_id', $service->id));
    }

    /** Nombre de places déjà prises, indexé "slotId|Y-m-d". */
    private function taken(Collection $slotIds, CarbonInterface $from, CarbonInterface $to): Collection
    {
        if ($slotIds->isEmpty()) {
            return collect();
        }

        return Appointment::withoutGlobalScopes()
            ->whereIn('time_slot_id', $slotIds)
            // whereDate : comparaison portable (MySQL DATE, SQLite texte horodaté).
            ->whereDate('appointment_date', '>=', $from->toDateString())
            ->whereDate('appointment_date', '<=', $to->toDateString())
            ->whereIn('status', AppointmentStatus::occupying())
            ->selectRaw('time_slot_id, appointment_date, COUNT(*) as total')
            ->groupBy('time_slot_id', 'appointment_date')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->time_slot_id.'|'.Carbon::parse($row->appointment_date)->toDateString() => (int) $row->total,
            ]);
    }

    /**
     * Dates (Y-m-d) ayant au moins une place libre.
     *
     * @return string[]
     */
    public function availableDates(Consulate $consulate, Service $service): array
    {
        $slots = $this->slotsQuery($consulate, $service)->get();
        $from = $this->firstBookableDate();
        $to = $this->lastBookableDate();
        $taken = $this->taken($slots->pluck('id'), $from, $to);
        $byWeekday = $slots->groupBy('weekday');
        $closed = ClosedDate::withoutGlobalScopes()->where('consulate_id', $consulate->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if (in_array($d->toDateString(), $closed, true)) {
                continue;
            }
            foreach ($byWeekday->get($d->dayOfWeek, collect()) as $slot) {
                $key = $slot->id.'|'.$d->toDateString();
                if ($slot->capacity - ($taken[$key] ?? 0) > 0) {
                    $dates[] = $d->toDateString();
                    break;
                }
            }
        }

        return $dates;
    }

    /**
     * Créneaux d'un jour avec le nombre de places restantes (`remaining`).
     *
     * @return Collection<int, TimeSlot>
     */
    public function slotsFor(Consulate $consulate, Service $service, CarbonInterface $date): Collection
    {
        if (! $this->isWithinWindow($date) || $this->isClosed($consulate, $date)) {
            return collect();
        }

        $slots = $this->slotsQuery($consulate, $service)
            ->where('weekday', $date->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        $taken = $this->taken($slots->pluck('id'), $date, $date);

        return $slots->each(function (TimeSlot $slot) use ($taken, $date) {
            $slot->remaining = max(0, $slot->capacity - ($taken[$slot->id.'|'.$date->toDateString()] ?? 0));
        })->filter(fn (TimeSlot $slot) => $slot->remaining > 0)->values();
    }
}
