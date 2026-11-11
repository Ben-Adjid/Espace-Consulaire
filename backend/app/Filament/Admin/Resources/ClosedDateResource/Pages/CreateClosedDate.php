<?php

namespace App\Filament\Admin\Resources\ClosedDateResource\Pages;

use App\Filament\Admin\Resources\ClosedDateResource;
use App\Models\ClosedDate;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClosedDate extends CreateRecord
{
    protected static string $resource = ClosedDateResource::class;

    /** Signale les rendez-vous touchés pour qu'ils soient reportés manuellement. */
    protected function afterCreate(): void
    {
        /** @var ClosedDate $closed */
        $closed = $this->record;
        $count = ClosedDateResource::affectedAppointments($closed);

        Notification::make()
            ->title($count > 0 ? "{$count} rendez-vous touché(s) par cette fermeture" : 'Fermeture enregistrée')
            ->body($count > 0 ? 'Contactez les demandeurs pour les reporter depuis la liste des rendez-vous.' : null)
            ->warning($count > 0)
            ->success($count === 0)
            ->send();
    }
}
