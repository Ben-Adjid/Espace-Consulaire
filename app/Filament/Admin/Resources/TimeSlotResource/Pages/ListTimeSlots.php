<?php

namespace App\Filament\Admin\Resources\TimeSlotResource\Pages;

use App\Filament\Admin\Resources\TimeSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeSlots extends ListRecords
{
    protected static string $resource = TimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}