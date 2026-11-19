<?php

namespace App\Filament\Admin\Resources\ConsularReceiptResource\Pages;

use App\Filament\Admin\Resources\ConsularReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConsularReceipt extends CreateRecord
{
    protected static string $resource = ConsularReceiptResource::class;

    /** L'agent connecté est enregistré comme encaisseur. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['received_by'] = auth()->id();
        $data['received_at'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
