<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Citizen;
use App\Models\PreRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Travail du jour pour le consulat (consul et personnel) : accueil des rendez-vous et dossiers à traiter.
 * Les requêtes passent par le global scope : uniquement les données du consulat de l'utilisateur.
 */
class TodayWorkWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Aujourd’hui au consulat';

    protected function getHeading(): ?string
    {
        return __($this->heading);
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ! $user->isSuperAdmin() && $user->consulate_id);
    }

    protected function getStats(): array
    {
        $active = [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value, AppointmentStatus::Completed->value];
        $todayQuery = fn () => Appointment::query()->whereDate('appointment_date', today())->whereIn('status', $active);

        $expected = $todayQuery()->count();
        $arrived = $todayQuery()->whereNotNull('checked_in_at')->count();
        $requests = PreRequest::query()->where('status', 'submitted')->count();
        $accounts = Citizen::query()->where('status', Citizen::STATUS_PENDING)->count();
        $overdue = count(PreRequest::overdueIds());
        $mine = PreRequest::query()->where('status', 'submitted')->where('assigned_to', auth()->id())->count();

        $queueUrl = \App\Filament\Admin\Pages\FileDuJour::canAccess() ? \App\Filament\Admin\Pages\FileDuJour::getUrl() : null;

        return [
            Stat::make(__('Rendez-vous du jour'), $expected)
                ->description(__(':arrived arrivé(s) · :waiting attendu(s)', ['arrived' => $arrived, 'waiting' => max(0, $expected - $arrived)]))
                ->icon('heroicon-o-calendar')->color('primary')
                ->url($queueUrl),
            Stat::make(__('Pré-demandes à examiner'), $requests)
                ->description($requests ? __('Soumises par des citoyens') : __('Rien en attente'))
                ->icon('heroicon-o-document-text')->color($requests ? 'warning' : 'success')
                ->url(\App\Filament\Admin\Resources\PreRequestResource::canViewAny() ? \App\Filament\Admin\Resources\PreRequestResource::getUrl() : null),
            Stat::make(__('Comptes citoyens à valider'), $accounts)
                ->description($accounts ? __('Vérification de résidence') : __('Rien en attente'))
                ->icon('heroicon-o-user-plus')->color($accounts ? 'warning' : 'success')
                ->url(\App\Filament\Admin\Resources\CitizenResource::canViewAny() ? \App\Filament\Admin\Resources\CitizenResource::getUrl() : null),
            // Suivi des délais et de la charge personnelle (liens vers la liste déjà filtrée).
            Stat::make(__('Dossiers en retard'), $overdue)
                ->description($overdue ? __('Délai cible du service dépassé') : __('Aucun retard'))
                ->icon('heroicon-o-exclamation-triangle')->color($overdue ? 'danger' : 'success')
                ->url($this->filteredUrl('overdue')),
            Stat::make(__('Mes dossiers'), $mine)
                ->description($mine ? __('Pré-demandes qui vous sont attribuées') : __('Aucun dossier attribué'))
                ->icon('heroicon-o-user')->color('gray')
                ->url($this->filteredUrl('mine')),
        ];
    }

    private function filteredUrl(string $filter): ?string
    {
        $resource = \App\Filament\Admin\Resources\PreRequestResource::class;

        return $resource::canViewAny() ? $resource::getUrl('index', ['tableFilters' => [$filter => ['isActive' => true]]]) : null;
    }
}
