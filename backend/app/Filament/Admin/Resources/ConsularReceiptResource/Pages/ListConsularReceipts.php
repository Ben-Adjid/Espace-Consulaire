<?php

namespace App\Filament\Admin\Resources\ConsularReceiptResource\Pages;

use App\Filament\Admin\Resources\ConsularReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsularReceipts extends ListRecords
{
    protected static string $resource = ConsularReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label(__('Nouvel encaissement'))];
    }
}
