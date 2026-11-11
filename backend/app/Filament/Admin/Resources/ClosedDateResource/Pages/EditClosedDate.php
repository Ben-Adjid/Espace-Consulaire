<?php

namespace App\Filament\Admin\Resources\ClosedDateResource\Pages;

use App\Filament\Admin\Resources\ClosedDateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClosedDate extends EditRecord
{
    protected static string $resource = ClosedDateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
