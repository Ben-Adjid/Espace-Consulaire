<?php

namespace App\Filament\Admin\Pages;

use App\Actions\CheckInAppointment;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

/**
 * File du jour pour le guichet : rendez-vous attendus, présents (avec ticket) et terminés.
 * Plein écran conseillé (touche F11). Rafraîchie toutes les 15 secondes.
 */
class FileDuJour extends Page
{
    use \App\Filament\Concerns\TranslatedPageLabels;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Accueil du public';

    protected static ?string $navigationLabel = 'File du jour';

    protected static ?string $title = 'File du jour';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.file-du-jour';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->can('manage_appointments'));
    }

    public function arrive(int $id): void
    {
        $appointment = Appointment::query()->findOrFail($id);
        try {
            $ticket = app(CheckInAppointment::class)->execute($appointment);
            Notification::make()->success()->title('Ticket '.CheckInAppointment::label($ticket->queue_number).' attribué')->send();
            $this->notifyDossier($appointment->fresh(['preRequest', 'service']));
        } catch (\RuntimeException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    /**
     * Au guichet : l'agent voit tout de suite si le dossier en ligne est validé, et quelles pièces originales présenter.
     * Sans dossier validé, il le signale : la démarche ne peut pas être traitée sur la seule présence du citoyen.
     */
    private function notifyDossier(Appointment $appointment): void
    {
        $documents = $appointment->service?->documentsList() ?? [];
        $pre = $appointment->preRequest;

        if (! $pre) {
            if ($documents !== []) {
                Notification::make()->warning()
                    ->title(__('Aucun dossier en ligne pour cette démarche'))
                    ->body(__('Pièces à vérifier sur place :').' '.implode(' · ', $documents))
                    ->persistent()->send();
            }

            return;
        }

        if ($pre->status !== 'validated') {
            Notification::make()->warning()
                ->title(__('Dossier en ligne').' : '.__(\App\Models\PreRequest::STATUSES[$pre->status] ?? $pre->status))
                ->body(__('Le dossier n’est pas validé : ne traitez pas la démarche avant validation.'))
                ->persistent()->send();

            return;
        }

        Notification::make()->info()
            ->title(__('Dossier en ligne validé : pièces originales à présenter'))
            ->body(implode(' · ', $documents))
            ->persistent()->send();
    }

    public function complete(int $id): void
    {
        $appointment = Appointment::query()->findOrFail($id);
        $appointment->update(['status' => AppointmentStatus::Completed]);
    }

    protected function getViewData(): array
    {
        $today = Appointment::query()
            ->whereDate('appointment_date', today())
            ->where('status', '!=', AppointmentStatus::Cancelled)
            ->with('service')
            ->get();

        $waiting = $today->filter(fn (Appointment $a) => $a->checked_in_at && $a->status !== AppointmentStatus::Completed)
            ->sortBy('queue_number')->values();
        $expected = $today->filter(fn (Appointment $a) => ! $a->checked_in_at && $a->status !== AppointmentStatus::Completed)
            ->sortBy('start_time')->values();
        $done = $today->filter(fn (Appointment $a) => $a->status === AppointmentStatus::Completed)->values();

        return compact('waiting', 'expected', 'done');
    }
}
