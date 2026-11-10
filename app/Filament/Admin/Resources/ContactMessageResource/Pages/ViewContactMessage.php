<?php

namespace App\Filament\Admin\Resources\ContactMessageResource\Pages;

use App\Filament\Admin\Resources\ContactMessageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    /** Ouvrir un message le marque automatiquement comme lu. */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! $this->record->is_read) {
            $this->record->update(['is_read' => true]);
        }
    }
}
