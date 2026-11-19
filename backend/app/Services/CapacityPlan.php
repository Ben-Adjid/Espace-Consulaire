<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Models\Consulate;
use App\Models\Service;
use App\Models\TimeSlot;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Plan de charge : pour chaque service, places ouvrables dans le mois (créneaux × jours d'ouverture)
 * face aux rendez-vous pris. Un jour fermé ne compte pas. Permet d'ajuster les créneaux au réel.
 */
class CapacityPlan
{
    /** @return array<int, array{service: string, places: int, pris: int, taux: int}> */
    public function build(Consulate $consulate, CarbonImmutable $month): array
    {
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();
        $closed = ClosedDate::withoutGlobalScopes()->where('consulate_id', $consulate->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->pluck('date')
            ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())->all();

        $slots = TimeSlot::withoutGlobalScopes()->where('consulate_id', $consulate->id)->where('is_active', true)->get();
        $services = Service::withoutGlobalScopes()->where('consulate_id', $consulate->id)->where('is_active', true)->where('allow_appointment', true)->get();

        $rows = [];
        foreach ($services as $service) {
            $places = 0;
            foreach (CarbonPeriod::create($from, $to) as $day) {
                if (in_array($day->toDateString(), $closed, true)) {
                    continue;
                }
                $places += $slots
                    ->filter(fn (TimeSlot $s) => $s->weekday === $day->dayOfWeek && ($s->service_id === null || $s->service_id === $service->id))
                    ->sum('capacity');
            }

            $pris = Appointment::withoutGlobalScopes()->where('consulate_id', $consulate->id)
                ->where('service_id', $service->id)
                ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
                ->whereIn('status', AppointmentStatus::occupying())
                ->count();

            $rows[] = [
                'service' => $service->getTranslation('name', 'fr'),
                'places' => $places,
                'pris' => $pris,
                'taux' => $places > 0 ? (int) round($pris / $places * 100) : 0,
            ];
        }

        return $rows;
    }
}
