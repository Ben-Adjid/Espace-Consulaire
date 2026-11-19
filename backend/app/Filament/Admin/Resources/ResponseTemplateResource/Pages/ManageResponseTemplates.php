<?php

namespace App\Filament\Admin\Resources\ResponseTemplateResource\Pages;

use App\Filament\Admin\Resources\ResponseTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageResponseTemplates extends ManageRecords
{
    protected static string $resource = ResponseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label(__('Nouveau modèle'))];
    }
}
