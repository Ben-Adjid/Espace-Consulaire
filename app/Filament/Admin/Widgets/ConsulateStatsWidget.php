<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ContactMessage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Statistiques du dashboard. Les requêtes passent par le global scope :
 * chaque consulat ne voit que ses chiffres, le super_admin voit le cumul.
 */
class ConsulateStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $active = [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value];

        $today = Appointment::query()->whereDate('appointment_date', today())
            ->whereIn('status', $active)->count();
        $upcoming = Appointment::query()->whereDate('appointment_date', '>', today())
            ->whereIn('status', $active)->count();
        $pending = Appointment::query()->where('status', AppointmentStatus::Pending->value)->count();
        $unread = ContactMessage::query()->where('is_read', false)->count();

        return [
            Stat::make("Rendez-vous aujourd'hui", $today)->icon('heroicon-o-calendar')->color('primary'),
            Stat::make('Rendez-vous à venir', $upcoming)->description("dont {$pending} en attente de confirmation")
                ->icon('heroicon-o-calendar-days')->color('warning'),
            Stat::make('Messages non lus', $unread)->icon('heroicon-o-envelope')->color($unread ? 'danger' : 'success'),
        ];
    }
}
