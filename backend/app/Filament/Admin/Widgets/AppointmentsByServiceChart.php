<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Widgets\ChartWidget;

/** Rendez-vous par service sur les 30 prochains jours (respecte le scope du consulat). */
class AppointmentsByServiceChart extends ChartWidget
{
    protected static ?string $heading = 'Rendez-vous par service (30 jours)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = Appointment::query()
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->whereBetween('appointments.appointment_date', [today()->toDateString(), today()->addDays(30)->toDateString()])
            ->whereIn('appointments.status', AppointmentStatus::occupying())
            ->selectRaw('services.name as name, COUNT(*) as total')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'datasets' => [[
                'label' => 'Rendez-vous',
                'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
                'backgroundColor' => '#4f46e5',
            ]],
            'labels' => $rows->pluck('name')->all(),
        ];
    }
}
