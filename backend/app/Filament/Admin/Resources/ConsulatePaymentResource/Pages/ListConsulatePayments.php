<?php

namespace App\Filament\Admin\Resources\ConsulatePaymentResource\Pages;

use App\Filament\Admin\Resources\ConsulatePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsulatePayments extends ListRecords
{
    protected static string $resource = ConsulatePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label(__('Enregistrer un paiement'))];
    }
}
