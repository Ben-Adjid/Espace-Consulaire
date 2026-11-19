<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tableau de bord mensuel : rendez-vous du mois, présences, absences, annulations,
 * comparés au mois précédent. Limité au consulat de l'utilisateur (périmètre global).
 */
class MonthlyActivityWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    /** Pilotage : réservé au consul et au super administrateur ; le personnel voit le travail du jour. */
    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->hasRole(\App\Models\User::ROLE_CONSULATE_ADMIN));
    }

    protected ?string $heading = 'Activité du mois';

    protected function getHeading(): ?string
    {
        return __($this->heading);
    }

    protected function getStats(): array
    {
        $start = now()->startOfMonth();
        $prevStart = $start->copy()->subMonth();

        $current = $this->counts($start->toDateString(), now()->endOfMonth()->toDateString());
        $previous = $this->counts($prevStart->toDateString(), $prevStart->copy()->endOfMonth()->toDateString());

        $trend = fn (int $now, int $before) => $before === 0
            ? __('Mois précédent : 0')
            : __(':pct % vs mois précédent', ['pct' => sprintf('%+d', (int) round(($now - $before) / $before * 100))]);

        $attendance = $current['past'] > 0 ? (int) round($current['present'] / $current['past'] * 100) : null;

        return [
            Stat::make(__('Rendez-vous du mois'), (string) $current['total'])
                ->description($trend($current['total'], $previous['total'])),
            Stat::make(__('Présents'), (string) $current['present'])
                ->description($attendance !== null ? __('Taux de présence : :pct %', ['pct' => $attendance]) : __('Aucun rendez-vous passé ce mois'))
                ->color('success'),
            Stat::make(__('Absences'), (string) $current['no_show'])
                ->description(__('Rendez-vous passés sans présentation'))
                ->color($current['no_show'] > 0 ? 'warning' : 'gray'),
            Stat::make(__('Annulations'), (string) $current['cancelled'])
                ->description($trend($current['cancelled'], $previous['cancelled'])),
        ];
    }

    /** @return array{total:int,present:int,no_show:int,cancelled:int,past:int} */
    private function counts(string $from, string $to): array
    {
        $base = fn (): Builder => Appointment::query()->whereBetween('appointment_date', [$from, $to]);
        $today = now()->toDateString();

        $present = $base()->where(fn (Builder $q) => $q->whereNotNull('checked_in_at')
            ->orWhere('status', AppointmentStatus::Completed))->count();

        $noShow = $base()->whereDate('appointment_date', '<', $today)
            ->whereNull('checked_in_at')
            ->whereIn('status', [AppointmentStatus::Pending, AppointmentStatus::Confirmed])->count();

        return [
            'total' => $base()->where('status', '!=', AppointmentStatus::Cancelled)->count(),
            'present' => $present,
            'no_show' => $noShow,
            'cancelled' => $base()->where('status', AppointmentStatus::Cancelled)->count(),
            'past' => $base()->whereDate('appointment_date', '<', $today)->where('status', '!=', AppointmentStatus::Cancelled)->count(),
        ];
    }
}
