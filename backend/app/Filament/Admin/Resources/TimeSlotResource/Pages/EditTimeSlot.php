<?php

namespace App\Filament\Admin\Resources\TimeSlotResource\Pages;

use App\Filament\Admin\Resources\TimeSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeSlot extends EditRecord
{
    protected static string $resource = TimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}