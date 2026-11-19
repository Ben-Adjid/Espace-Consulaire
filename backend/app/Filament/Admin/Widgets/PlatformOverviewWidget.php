<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\Consulate;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Vue plateforme pour le super administrateur : état des consulats, abonnements et activité globale. */
class PlatformOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Plateforme';

    protected function getHeading(): ?string
    {
        return __($this->heading);
    }

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $states = Consulate::query()->get()->map(fn (Consulate $c) => $c->is_active ? $c->subscriptionState() : 'inactive');
        $online = $states->filter(fn ($s) => $s !== 'inactive' && $s !== 'suspended')->count();
        $toRenew = $states->filter(fn ($s) => in_array($s, ['expiring', 'grace'], true))->count();
        $blocked = $states->filter(fn ($s) => in_array($s, ['inactive', 'suspended'], true))->count();
        // Consulats qui n'ont jamais enregistré d'abonnement : à signaler, jamais affichés comme « à jour ».
        $withoutSubscription = $states->filter(fn ($s) => $s === 'none')->count();

        $month = Appointment::query()
            ->whereBetween('appointment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        return [
            Stat::make(__('Consulats en ligne'), $online)
                ->description(__(':count fermé(s) ou suspendu(s)', ['count' => $blocked]))
                ->icon('heroicon-o-building-library')->color('primary')
                ->url(\App\Filament\Admin\Resources\ConsulateResource::getUrl()),
            Stat::make(__('Abonnements à renouveler'), $toRenew)
                ->description(match (true) {
                    $toRenew > 0 => __('Échéance proche ou délai de grâce'),
                    $withoutSubscription > 0 => __(':count sans abonnement enregistré', ['count' => $withoutSubscription]),
                    default => __('Tous à jour'),
                })
                ->icon('heroicon-o-arrow-path')->color(($toRenew > 0 || $withoutSubscription > 0) ? 'warning' : 'success')
                ->url(\App\Filament\Admin\Resources\ConsulateResource::getUrl()),
            Stat::make(__('Citoyens inscrits'), Citizen::query()->count())
                ->description(__(':count rendez-vous ce mois-ci, tous consulats', ['count' => $month]))
                ->icon('heroicon-o-users')->color('gray'),
        ];
    }
}
