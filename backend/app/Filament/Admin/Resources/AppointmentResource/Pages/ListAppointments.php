<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Exporter en CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    /**
     * Export des rendez-vous visibles pour le compte connecté (le global scope limite au consulat).
     * Contient des données personnelles : à manipuler avec précaution.
     */
    private function exportCsv(): StreamedResponse
    {
        $rows = Appointment::query()->with(['service', 'consulate'])->orderBy('appointment_date')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, ['Référence', 'Consulat', 'Date', 'Heure', 'Service', 'Nom', 'Email', 'Téléphone', 'Statut'], ';');
            foreach ($rows as $a) {
                fputcsv($out, [
                    $a->reference, $a->consulate?->slug, $a->appointment_date->toDateString(),
                    substr((string) $a->start_time, 0, 5), $a->service?->name, $a->full_name,
                    $a->email, $a->phone, $a->status->getLabel(),
                ], ';');
            }
            fclose($out);
        }, 'rendez-vous-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
