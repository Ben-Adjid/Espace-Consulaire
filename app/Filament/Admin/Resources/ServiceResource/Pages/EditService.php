<?php

namespace App\Filament\Admin\Resources\ServiceResource\Pages;

use App\Filament\Admin\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\LocaleSwitcher::make(), Actions\DeleteAction::make()];
    }
}