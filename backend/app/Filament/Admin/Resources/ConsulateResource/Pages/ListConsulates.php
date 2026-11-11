<?php

namespace App\Filament\Admin\Resources\ConsulateResource\Pages;

use App\Filament\Admin\Resources\ConsulateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsulates extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = ConsulateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\LocaleSwitcher::make(), Actions\CreateAction::make()];
    }
}