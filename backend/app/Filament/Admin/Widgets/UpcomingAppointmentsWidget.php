<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingAppointmentsWidget extends TableWidget
{
    protected static ?string $heading = 'Prochains rendez-vous';

    protected function getTableHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return __(static::$heading);
    }

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->with('service')
                    ->whereDate('appointment_date', '>=', today())
                    ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
                    ->orderBy('appointment_date')->orderBy('start_time')
            )
            ->paginated([5])
            ->columns([
                Tables\Columns\TextColumn::make('appointment_date')->label(__('Date'))->date('d/m/Y'),
                Tables\Columns\TextColumn::make('start_time')->label(__('Heure'))
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                Tables\Columns\TextColumn::make('service.name')->label(__('Service')),
                Tables\Columns\TextColumn::make('full_name')->label(__('Demandeur')),
                Tables\Columns\TextColumn::make('reference')->label(__('Référence')),
                Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge(),
            ]);
    }
}
