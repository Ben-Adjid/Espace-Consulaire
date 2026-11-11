<?php

namespace App\Filament\Admin\Resources\ClosedDateResource\Pages;

use App\Filament\Admin\Resources\ClosedDateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClosedDates extends ListRecords
{
    protected static string $resource = ClosedDateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
