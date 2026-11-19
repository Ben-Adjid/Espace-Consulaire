<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ConsularReceipt;
use App\Models\Consulate;
use Carbon\CarbonImmutable;

/**
 * Rapport mensuel d'un consulat : par service, rendez-vous, présences, absences, annulations,
 * et montant encaissé (reçus valides). Les données personnelles ne figurent jamais dans le rapport.
 */
class MonthlyReport
{
    /** @return array{month: string, rows: array<int, array<string, int|string>>, totals: array<string, int|string>} */
    public function build(Consulate $consulate, CarbonImmutable $month): array
    {
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();
        $today = CarbonImmutable::today()->toDateString();

        $appointments = Appointment::withoutGlobalScopes()
            ->where('consulate_id', $consulate->id)
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->with('service')
            ->get();

        $rows = [];
        foreach ($appointments->groupBy(fn (Appointment $a) => $a->service?->name ?? '—') as $service => $items) {
            $present = $items->filter(fn (Appointment $a) => $a->checked_in_at !== null || $a->status === AppointmentStatus::Completed)->count();
            $noShow = $items->filter(fn (Appointment $a) => $a->checked_in_at === null
                && $a->status !== AppointmentStatus::Completed
                && $a->status !== AppointmentStatus::Cancelled
                && $a->appointment_date->toDateString() < $today)->count();
            $cancelled = $items->filter(fn (Appointment $a) => $a->status === AppointmentStatus::Cancelled)->count();

            $rows[] = [
                'service' => $service,
                'rendez_vous' => $items->count(),
                'presents' => $present,
                'absences' => $noShow,
                'annules' => $cancelled,
            ];
        }

        $receipts = ConsularReceipt::withoutGlobalScopes()
            ->where('consulate_id', $consulate->id)
            ->whereNull('cancelled_at')
            ->whereBetween('received_at', [$from->startOfDay(), $to->endOfDay()])
            ->get();

        $totals = [
            'service' => 'TOTAL',
            'rendez_vous' => array_sum(array_column($rows, 'rendez_vous')),
            'presents' => array_sum(array_column($rows, 'presents')),
            'absences' => array_sum(array_column($rows, 'absences')),
            'annules' => array_sum(array_column($rows, 'annules')),
            'encaissement' => (string) $receipts->sum('amount'),
        ];

        return ['month' => $from->format('Y-m'), 'rows' => $rows, 'totals' => $totals];
    }

    /**
     * Traitement des pré-demandes du mois : volumes par décision, délai moyen de décision (jours),
     * et dossiers actuellement en retard. Sert au rapport imprimable et à la comparaison entre consulats.
     *
     * @return array{submitted:int, validated:int, returned:int, rejected:int, pending:int, avg_days:?float, overdue:int}
     */
    public function caseStats(Consulate $consulate, CarbonImmutable $month): array
    {
        $from = $month->startOfMonth()->startOfDay();
        $to = $month->endOfMonth()->endOfDay();

        $submitted = \App\Models\PreRequest::withoutGlobalScopes()->where('consulate_id', $consulate->id)
            ->whereBetween('submitted_at', [$from, $to])->get();
        $reviewed = \App\Models\PreRequest::withoutGlobalScopes()->where('consulate_id', $consulate->id)
            ->whereBetween('reviewed_at', [$from, $to])->whereNotNull('submitted_at')->get();
        $overdue = \App\Models\PreRequest::withoutGlobalScopes()->where('consulate_id', $consulate->id)
            ->where('status', 'submitted')->with('service')->get()->filter->isOverdue()->count();

        return [
            'submitted' => $submitted->count(),
            'validated' => $reviewed->where('status', 'validated')->count(),
            'returned' => $reviewed->where('status', 'returned')->count(),
            'rejected' => $reviewed->where('status', 'rejected')->count(),
            'pending' => $submitted->where('status', 'submitted')->count(),
            'avg_days' => $reviewed->isEmpty() ? null
                : round($reviewed->avg(fn ($p) => $p->submitted_at->diffInHours($p->reviewed_at) / 24), 1),
            'overdue' => $overdue,
        ];
    }

    /** CSV séparé par des points-virgules (ouverture directe dans Excel), encodé UTF-8 avec BOM. */
    public function toCsv(array $report): string
    {
        $lines = ["\xEF\xBB\xBF".implode(';', ['Service', 'Rendez-vous', 'Présents', 'Absences', 'Annulés'])];
        foreach ($report['rows'] as $r) {
            $lines[] = implode(';', [$r['service'], $r['rendez_vous'], $r['presents'], $r['absences'], $r['annules']]);
        }
        $t = $report['totals'];
        $lines[] = implode(';', [$t['service'], $t['rendez_vous'], $t['presents'], $t['absences'], $t['annules']]);
        $lines[] = 'Montant encaissé (reçus valides);'.$t['encaissement'];

        return implode("\r\n", $lines)."\r\n";
    }
}
