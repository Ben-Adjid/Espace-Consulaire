<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /** Horodate la confirmation lors d'un passage au statut « confirmé ». */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $new = $data['status'] ?? null;
        $new = $new instanceof AppointmentStatus ? $new->value : $new;

        if ($new === AppointmentStatus::Confirmed->value && ! $this->record->confirmed_at) {
            $data['confirmed_at'] = now();
        }

        return $data;
    }
}
