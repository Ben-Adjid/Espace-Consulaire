<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\CitizenReport;
use App\Models\Consulate;
use App\Models\PreRequest;
use Carbon\CarbonImmutable;

/**
 * Vue d'ensemble de la plateforme pour le super admin : un ligne par consulat et des totaux.
 * Chaque indicateur est compté sans le périmètre d'un consulat, puis regroupé par consulat_id.
 */
class GlobalDashboard
{
    public const SUBSCRIPTION_LABELS = [
        'none' => 'Aucun abonnement',
        'active' => 'Actif',
        'expiring' => 'Expire bientôt',
        'grace' => 'Délai de grâce',
        'suspended' => 'Suspendu',
    ];

    /** @return array{rows: array<int, array<string, mixed>>, totals: array<string, int>} */
    public function build(CarbonImmutable $month): array
    {
        $from = $month->startOfMonth()->toDateString();
        $to = $month->endOfMonth()->toDateString();
        $today = now()->toDateString();

        $appointments = Appointment::withoutGlobalScopes()
            ->whereBetween('appointment_date', [$from, $to])
            ->selectRaw('consulate_id, COUNT(*) AS total, SUM(status = ?) AS cancelled, SUM(checked_in_at IS NOT NULL OR status = ?) AS present',
                [AppointmentStatus::Cancelled->value, AppointmentStatus::Completed->value])
            ->groupBy('consulate_id')->get()->keyBy('consulate_id');

        $citizens = Citizen::withoutGlobalScopes()->where('status', Citizen::STATUS_ACTIVE)
            ->selectRaw('consulate_id, COUNT(*) AS n')->groupBy('consulate_id')->pluck('n', 'consulate_id');

        $preRequests = PreRequest::withoutGlobalScopes()->where('status', 'submitted')
            ->selectRaw('consulate_id, COUNT(*) AS n')->groupBy('consulate_id')->pluck('n', 'consulate_id');

        $reports = CitizenReport::withoutGlobalScopes()->whereIn('status', ['new', 'in_progress'])
            ->selectRaw('consulate_id, COUNT(*) AS n')->groupBy('consulate_id')->pluck('n', 'consulate_id');

        $reports2 = app(MonthlyReport::class);
        $rows = Consulate::query()->orderBy('slug')->get()->map(function (Consulate $c) use ($appointments, $citizens, $preRequests, $reports, $reports2, $month) {
            // Comparaison : taux de présence, délai moyen de décision et retards (calculs du rapport mensuel).
            $cases = $reports2->caseStats($c, $month);
            $a = $appointments->get($c->id);
            $state = $c->subscriptionState();

            return [
                'slug' => $c->slug,
                'name' => $c->getTranslation('name', app()->getLocale()),
                'is_active' => (bool) $c->is_active,
                'subscription' => self::SUBSCRIPTION_LABELS[$state] ?? $state,
                'subscription_state' => $state,
                'subscription_until' => $c->subscription_until?->toDateString(),
                'citizens' => (int) ($citizens[$c->id] ?? 0),
                'appointments' => (int) ($a->total ?? 0) - (int) ($a->cancelled ?? 0),
                'present' => (int) ($a->present ?? 0),
                'pre_requests' => (int) ($preRequests[$c->id] ?? 0),
                'open_reports' => (int) ($reports[$c->id] ?? 0),
                'attendance' => ($a->total ?? 0) - ($a->cancelled ?? 0) > 0
                    ? (int) round((int) ($a->present ?? 0) / ((int) $a->total - (int) $a->cancelled) * 100) : null,
                'avg_days' => $cases['avg_days'],
                'overdue' => $cases['overdue'],
            ];
        })->values()->all();

        $totals = [
            'consulates' => count($rows),
            'active' => collect($rows)->where('is_active', true)->count(),
            'suspended' => collect($rows)->where('subscription_state', 'suspended')->count(),
            'citizens' => collect($rows)->sum('citizens'),
            'appointments' => collect($rows)->sum('appointments'),
            'present' => collect($rows)->sum('present'),
            'pre_requests' => collect($rows)->sum('pre_requests'),
            'open_reports' => collect($rows)->sum('open_reports'),
        ];

        return compact('rows', 'totals');
    }
}
